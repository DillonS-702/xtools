<?php
/**
 * This file contains only the LabsHelper class.
 */

namespace AppBundle\Helper;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Labs helper provides information relating to the WMF Labs installation of XTools.
 */
class LabsHelper
{
    /** @var string The current database name. */
    protected $dbName;

    /** @var Connection The database connection. */
    protected $client;

    /** @var ContainerInterface The DI container. */
    protected $container;

    /** @var string The project URL. */
    protected $url;

    /**
     * LabsHelper constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Check to see if a given tool is enabled.
     * @param string $tool The tool short name.
     * @return bool
     */
    public function checkEnabled($tool)
    {
        if (!$this->container->getParameter("enable.$tool")) {
            throw new NotFoundHttpException('This tool is disabled');
        }
    }

    /**
     * Is xTools connecting to WMF Labs?
     *
     * @return boolean
     */
    public function isLabs()
    {
        return (bool)$this->container->getParameter('app.is_labs');
    }

    /**
     * Get the record for the given project in the meta.wiki table
     * @param  string $project Valid project in the formats:
     *                         https://en.wikipedia.org, en.wikipedia, enwiki
     * @return array|false     Database record or false if no record was found.
     *                         Relevant values returned include the 'dbname' (enwiki),
     *                         'lang', 'name' (Wikipedia) and 'url' (https://en.wikipeda.org)
     */
    private function getProjectMetadata($project)
    {
        // First, run through our project map.  This is expected to return back
        // to the project name if there is no defined mapping.
        if ($this->container->hasParameter("app.project.$project")) {
            $project = $this->container->getParameter("app.project.$project");
        }

        // If this is a single-project setup, manually construct the metadata.
        if ($this->container->getParameter("app.single_wiki")) {
            return [
                'dbname' => $this->container->getParameter('database_replica_name'),
                'url' => $this->container->getParameter('wiki_url'),
                'lang' => $this->container->getParameter('lang'),
                'name' => 'Xtools', // Not used?
            ];
        }

        // Grab the connection to the meta database
        $this->client = $this->container
            ->get('doctrine')
            ->getManager('meta')
            ->getConnection();

        // Create the query we're going to run against the meta database
        $wikiQuery = $this->client->createQueryBuilder();
        $wikiQuery
            ->select([ 'dbname', 'name', 'url', 'lang' ])
            ->from('wiki')
            ->where($wikiQuery->expr()->eq('dbname', ':project'))
            // The meta database will have the project's URL stored as https://en.wikipedia.org
            // so we need to query for it accordingly, trying different variations the user
            // might have inputted.
            ->orwhere($wikiQuery->expr()->like('url', ':projectUrl'))
            ->orwhere($wikiQuery->expr()->like('url', ':projectUrl2'))
            ->setParameter('project', $project)
            ->setParameter('projectUrl', "https://$project")
            ->setParameter('projectUrl2', "https://$project.org");
        $wikiStatement = $wikiQuery->execute();

        // Fetch the wiki data
        $wikis = $wikiStatement->fetchAll();

        // Return false if we can't find the wiki
        if (count($wikis) < 1) {
            return false;
        }

        // Otherwise, return the first result (in the rare event there are more than one).
        return $wikis[0];
    }

    /**
     * Get a list of all projects.
     */
    public function allProjects()
    {
        $wikiQuery = $this->client->createQueryBuilder();
        $wikiQuery->select([ 'dbName', 'name', 'url' ])->from('wiki');
        $stmt = $wikiQuery->execute();
        $out = $stmt->fetchAll();
        return $out;
    }

    /**
     * All mapping tables to environment-specific names, as specified in config/table_map.yml
     * Used for example to convert revision -> revision_replica
     * https://wikitech.wikimedia.org/wiki/Help:Tool_Labs/Database#Tables_for_revision_or_logging_queries_involving_user_names_and_IDs
     *
     * @param string $table  Table name
     * @param string $dbName Database name
     * @param string|null $table_extension Optional table extension, which will only get used if we're on labs.
     *
     * @return string Converted table name
     */
    public function getTable($table, $dbName = null, $table_extension = null)
    {
        // This is a workaround for a one-to-many mapping
        // as required by Labs.  We combine $table with
        // $table_extension in order to generate the new table name
        if ($this->isLabs() && $table_extension !== null) {
            $table = $table . "_" . $table_extension;
        }

        // Use the table specified in the table mapping configuration, if present.
        $mapped = false;
        if ($this->container->hasParameter("app.table.$table")) {
            $mapped = true;
            $table = $this->container->getParameter("app.table.$table");
        }

        // Figure out database name.
        // Use class variable for the database name if not set via function parameter.
        $dbNameActual = $dbName ? $dbName : $this->dbName;
        if ($this->isLabs() && substr($dbNameActual, -2) != '_p') {
            // Append '_p' if this is labs.
            $dbNameActual .= '_p';
        }

        return $dbNameActual ? "$dbNameActual.$table" : $table;
    }

    // TODO: figure out how to use Doctrine to query host 'tools-db'
}
