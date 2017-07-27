<?php
/**
 * This file contains only the AppExtension class.
 */

namespace AppBundle\Twig;

/**
 * Twig functions and filters for XTools.
 */
class AppExtension extends Extension
{

    /**
     * Get the name of this extension.
     * @return string
     */
    public function getName()
    {
        return 'app_extension';
    }

    /*********************************** FUNCTIONS ***********************************/

    /**
     * Get all functions that this class provides.
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new \Twig_SimpleFunction('request_time', [ $this, 'requestTime' ], $options),
            new \Twig_SimpleFunction('memory_usage', [ $this, 'requestMemory' ], $options),
            new \Twig_SimpleFunction('year', [ $this, 'generateYear' ], $options),
            new \Twig_SimpleFunction('msgPrintExists', [ $this, 'intuitionMessagePrintExists' ], $options),
            new \Twig_SimpleFunction('msgExists', [ $this, 'intuitionMessageExists' ], $options),
            new \Twig_SimpleFunction('msg', [ $this, 'intuitionMessage' ], $options),
            new \Twig_SimpleFunction('lang', [ $this, 'getLang' ], $options),
            new \Twig_SimpleFunction('langName', [ $this, 'getLangName' ], $options),
            new \Twig_SimpleFunction('allLangs', [ $this, 'getAllLangs' ]),
            new \Twig_SimpleFunction('isRTL', [ $this, 'intuitionIsRTL' ]),
            new \Twig_SimpleFunction('isRTLLang', [ $this, 'intuitionIsRTLLang' ]),
            new \Twig_SimpleFunction('shortHash', [ $this, 'gitShortHash' ]),
            new \Twig_SimpleFunction('hash', [ $this, 'gitHash' ]),
            new \Twig_SimpleFunction('enabled', [ $this, 'tabEnabled' ]),
            new \Twig_SimpleFunction('tools', [ $this, 'allTools' ]),
            new \Twig_SimpleFunction('color', [ $this, 'getColorList' ]),
            new \Twig_SimpleFunction('chartColor', [ $this, 'chartColor' ]),
            new \Twig_SimpleFunction('isWMFLabs', [ $this, 'isWMFLabs' ]),
            new \Twig_SimpleFunction('isSingleWiki', [ $this, 'isSingleWiki' ]),
            new \Twig_SimpleFunction('getReplagThreshold', [ $this, 'getReplagThreshold' ]),
            new \Twig_SimpleFunction('loadStylesheetsFromCDN', [ $this, 'loadStylesheetsFromCDN' ]),
            new \Twig_SimpleFunction('isWMFLabs', [ $this, 'isWMFLabs' ]),
            new \Twig_SimpleFunction('replag', [ $this, 'replag' ]),
            new \Twig_SimpleFunction('link', [ $this, 'link' ]),
            new \Twig_SimpleFunction('quote', [ $this, 'quote' ]),
            new \Twig_SimpleFunction('bugReportURL', [ $this, 'bugReportURL' ]),
            new \Twig_SimpleFunction('logged_in_user', [$this, 'functionLoggedInUser']),
            new \Twig_SimpleFunction('isUserAnon', [$this, 'isUserAnon']),
            new \Twig_SimpleFunction('nsName', [$this, 'nsName']),
        ];
    }

    /**
     * Get the duration of the current HTTP request in microseconds.
     * @param int $decimals
     * @return string
     */
    public function requestTime($decimals = 3)
    {
        return number_format(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], $decimals);
    }

    /**
     * Get the formatted real memory usage.
     * @return float
     */
    public function requestMemory()
    {
        $mem = memory_get_usage(false);
        $div = pow(1024, 2);
        $mem = $mem / $div;

        return round($mem, 2);
    }

    /**
     * Get the current year.
     * @return string
     */
    public function generateYear()
    {
        return date('Y');
    }

    /**
     * See if a given i18n message exists.
     * @TODO: refactor all intuition stuff so it can be used anywhere
     * @param string $message The message.
     * @return bool
     */
    public function intuitionMessageExists($message = "")
    {
        return $this->getIntuition()->msgExists($message, [ "domain" => "xtools" ]);
    }

    /**
     * Get an i18n message if it exists, otherwise just get the message key.
     * @param string $message
     * @param array $vars
     * @return mixed|null|string
     */
    public function intuitionMessagePrintExists($message = "", $vars = [])
    {
        if (is_array($message)) {
            $vars = $message;
            $message = $message[0];
            $vars = array_slice($vars, 1);
        }
        if ($this->intuitionMessageExists($message)) {
            return $this->intuitionMessage($message, $vars);
        } else {
            return $message;
        }
    }

    /**
     * Get an i18n message.
     * @param string $message
     * @param array $vars
     * @return mixed|null|string
     */
    public function intuitionMessage($message = "", $vars = [])
    {
        return $this->getIntuition()->msg($message, [ "domain" => "xtools", "variables" => $vars ]);
    }

    /**
     * Get the current language code.
     * @return string
     */
    public function getLang()
    {
        return $this->getIntuition()->getLang();
    }

    /**
     * Get the current language name (defaults to 'English').
     * @return string
     */
    public function getLangName()
    {
        return in_array(ucfirst($this->getIntuition()->getLangName()), $this->getAllLangs())
            ? $this->getIntuition()->getLangName()
            : 'English';
    }

    /**
     * Get all available languages in the i18n directory
     * @return array Associative array of langKey => langName
     */
    public function getAllLangs()
    {
        $messageFiles = glob($this->container->getParameter("kernel.root_dir") . '/../i18n/*.json');

        $languages = array_values(array_unique(array_map(
            function ($filename) {
                return basename($filename, '.json');
            },
            $messageFiles
        )));

        $availableLanguages = [];

        foreach ($languages as $lang) {
            $availableLanguages[$lang] = ucfirst($this->getIntuition()->getLangName($lang));
        }
        asort($availableLanguages);

        return $availableLanguages;
    }

    /**
     * Whether the current language is right-to-left.
     * @return bool
     */
    public function intuitionIsRTL()
    {
        return $this->getIntuition()->isRTL($this->getIntuition()->getLang());
    }

    /**
     * Whether the given language is right-to-left.
     * @param string $lang The language code.
     * @return bool
     */
    public function intuitionIsRTLLang($lang)
    {
        return $this->getIntuition()->isRTL($lang);
    }

    /**
     * Get the short hash of the currently checked-out Git commit.
     * @return string
     */
    public function gitShortHash()
    {
        return exec("git rev-parse --short HEAD");
    }

    /**
     * Get the full hash of the currently checkout-out Git commit.
     * @return string
     */
    public function gitHash()
    {
        return exec("git rev-parse HEAD");
    }

    /**
     * Check whether a given tool is enabled.
     * @param string $tool The short name of the tool.
     * @return bool
     */
    public function tabEnabled($tool = "index")
    {
        $param = false;
        if ($this->container->hasParameter("enable.$tool")) {
            $param = boolval($this->container->getParameter("enable.$tool"));
        }
        return $param;
    }

    /**
     * Get a list of the short names of all tools.
     * @return string[]
     */
    public function allTools()
    {
        $retVal = [];
        if ($this->container->hasParameter("tools")) {
            $retVal = $this->container->getParameter("tools");
        }
        return $retVal;
    }

    /**
     * Get a list of namespace colours (one or all).
     * @param bool $num The NS ID to get.
     * @return string[]|string Indexed by namespace ID.
     */
    public static function getColorList($num = false)
    {
        $colors = [
            0 => '#FF5555',
            1 => '#55FF55',
            2 => '#FFEE22',
            3 => '#FF55FF',
            4 => '#5555FF',
            5 => '#55FFFF',
            6 => '#C00000',
            7 => '#0000C0',
            8 => '#008800',
            9 => '#00C0C0',
            10 => '#FFAFAF',
            11 => '#808080',
            12 => '#00C000',
            13 => '#404040',
            14 => '#C0C000',
            15 => '#C000C0',
            90 => '#991100',
            91 => '#99FF00',
            92 => '#000000',
            93 => '#777777',
            100 => '#75A3D1',
            101 => '#A679D2',
            102 => '#660000',
            103 => '#000066',
            104 => '#FAFFAF',
            105 => '#408345',
            106 => '#5c8d20',
            107 => '#e1711d',
            108 => '#94ef2b',
            109 => '#756a4a',
            110 => '#6f1dab',
            111 => '#301e30',
            112 => '#5c9d96',
            113 => '#a8cd8c',
            114 => '#f2b3f1',
            115 => '#9b5828',
            116 => '#002288',
            117 => '#0000CC',
            118 => '#99FFFF',
            119 => '#99BBFF',
            120 => '#FF99FF',
            121 => '#CCFFFF',
            122 => '#CCFF00',
            123 => '#CCFFCC',
            200 => '#33FF00',
            201 => '#669900',
            202 => '#666666',
            203 => '#999999',
            204 => '#FFFFCC',
            205 => '#FF00CC',
            206 => '#FFFF00',
            207 => '#FFCC00',
            208 => '#FF0000',
            209 => '#FF6600',
            250 => '#6633CC',
            251 => '#6611AA',
            252 => '#66FF99',
            253 => '#66FF66',
            446 => '#06DCFB',
            447 => '#892EE4',
            460 => '#99FF66',
            461 => '#99CC66',
            470 => '#CCCC33',
            471 => '#CCFF33',
            480 => '#6699FF',
            481 => '#66FFFF',
            484 => '#07C8D6',
            485 => '#2AF1FF',
            486 => '#79CB21',
            487 => '#80D822',
            490 => '#995500',
            491 => '#998800',
            710 => '#FFCECE',
            711 => '#FFC8F2',
            828 => '#F7DE00',
            829 => '#BABA21',
            866 => '#FFFFFF',
            867 => '#FFCCFF',
            1198 => '#FF34B3',
            1199 => '#8B1C62',
            2300 => '#A900B8',
            2301 => '#C93ED6',
            2302 => '#8A09C1',
            2303 => '#974AB8',
            2600 => '#000000',
        ];

        if ($num === false) {
            return $colors;
        } elseif (isset($colors[$num])) {
            return $colors[$num];
        } else {
            // Default to grey.
            return '#CCC';
        }
    }

    /**
     * Get color-blind friendly colors for use in charts
     * @param  Integer $num Index of color
     * @return String RGBA color (so you can more easily adjust the opacity)
     */
    public function chartColor($num)
    {
        $colors = [
            'rgba(171, 212, 235, 1)',
            'rgba(178, 223, 138, 1)',
            'rgba(251, 154, 153, 1)',
            'rgba(253, 191, 111, 1)',
            'rgba(202, 178, 214, 1)',
            'rgba(207, 182, 128, 1)',
            'rgba(141, 211, 199, 1)',
            'rgba(252, 205, 229, 1)',
            'rgba(255, 247, 161, 1)',
            'rgba(217, 217, 217, 1)',
        ];

        return $colors[$num % count($colors)];
    }

    /**
     * Whether XTools is running in single-project mode.
     * @return bool
     */
    public function isSingleWiki()
    {
        $param = true;
        if ($this->container->hasParameter('app.single_wiki')) {
            $param = boolval($this->container->getParameter('app.single_wiki'));
        }
        return $param;
    }

    /**
     * Get the database replication-lag threshold.
     * @return int
     */
    public function getReplagThreshold()
    {
        $param = 30;
        if ($this->container->hasParameter('app.replag_threshold')) {
            $param = $this->container->getParameter('app.replag_threshold');
        };
        return $param;
    }

    /**
     * Whether we should load stylesheets from external CDNs or not.
     * @return bool
     */
    public function loadStylesheetsFromCDN()
    {
        $param = false;
        if ($this->container->hasParameter('app.load_stylesheets_from_cdn')) {
            $param = boolval($this->container->getParameter('app.load_stylesheets_from_cdn'));
        }
        return $param;
    }

    /**
     * Whether XTools is running in WMF Labs mode.
     * @return bool
     */
    public function isWMFLabs()
    {
        $param = false;
        if ($this->container->hasParameter('app.is_labs')) {
            $param = boolval($this->container->getParameter('app.is_labs'));
        }
        return $param;
    }

    /**
     * The current replication lag.
     * @return int
     */
    public function replag()
    {
        $retVal = 0;

        if ($this->isWMFLabs()) {
            $project = $this->container->get('request_stack')->getCurrentRequest()->get('project');

            if (!isset($project)) {
                $project = 'enwiki';
            }

            $stmt = "SELECT lag FROM `heartbeat_p`.`heartbeat` h
            RIGHT JOIN `meta_p`.`wiki` w ON concat(h.shard, \".labsdb\")=w.slice
            WHERE dbname LIKE :project OR name LIKE :project OR url LIKE :project LIMIT 1";

            $conn = $this->container->get('doctrine')->getManager('replicas')->getConnection();

            // Prepare the query and execute
            $resultQuery = $conn->prepare($stmt);
            $resultQuery->bindParam('project', $project);
            $resultQuery->execute();

            if ($resultQuery->errorCode() == 0) {
                $results = $resultQuery->fetchAll();

                if (isset($results[0]['lag'])) {
                    $retVal = $results[0]['lag'];
                }
            }
        }

        return $retVal;
    }

    /**
     * Generate an XTools URL.
     * @deprecated Use path() instead.
     * @param string $path The path.
     * @return string
     */
    public function link($path = "/")
    {
        $base_path = $this->container->getParameter("app.base_path");
        $retVal = $path;

        if (isset($base_path)) {
            $retVal = "$base_path/$path";
        }

        $retVal = str_replace("//", "/", $retVal);

        return $retVal;
    }

    /**
     * Get a random quote for the footer
     * @return string
     */
    public function quote()
    {
        $quotes = $this->container->getParameter('quotes');
        $id = array_rand($quotes);
        return $quotes[$id];
    }

    /**
     * Get the currently logged in user's details.
     * @return string[]
     */
    public function functionLoggedInUser()
    {
        return $this->container->get('session')->get('logged_in_user');
    }


    /*********************************** FILTERS ***********************************/

    /**
     * Get all filters for this extension.
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('capitalize_first', [ $this, 'capitalizeFirst' ]),
            new \Twig_SimpleFilter('percent_format', [ $this, 'percentFormat' ]),
        ];
    }

    /**
     * Mysteriously missing Twig helper to capitalize only the first character.
     * E.g. used for table headings for translated messages
     * @param  string $str The string
     * @return string      The string, capitalized
     */
    public function capitalizeFirst($str)
    {
        return ucfirst($str);
    }

    /**
     * Format a given number or fraction as a percentage
     * @param  number  $numerator     Numerator or single fraction if denominator is ommitted
     * @param  number  [$denominator] Denominator
     * @param  integer [$precision]   Number of decimal places to show
     * @return string                 Formatted percentage
     */
    public function percentFormat($numerator, $denominator = null, $precision = 1)
    {
        if (!$denominator) {
            $quotient = $numerator;
        } else {
            $quotient = ( $numerator / $denominator ) * 100;
        }

        return round($quotient, $precision) . '%';
    }

    /**
     * Helper to return whether the given user is an anonymous (logged out) user
     * @param  User|string $user User object or username as a string
     * @return bool
     */
    public function isUserAnon($user)
    {
        if ($user instanceof User) {
            $username = $user.username;
        } else {
            $username = $user;
        }

        return filter_var($username, FILTER_VALIDATE_IP);
    }

    /**
     * Helper to properly translate a namespace name
     * @param  int|string $namespace Namespace key as a string or ID
     * @param  array      $namespaces List of available namespaces
     *                                as retrieved from Project::getNamespaces
     * @return string Namespace name
     */
    public function nsName($namespace, $namespaces)
    {
        if ($namespace === 'all') {
            return $this->getIntuition()->msg('all');
        } elseif ($namespace === '0' || $namespace === 0 || $namespace === 'Main') {
            return $this->getIntuition()->msg('mainspace');
        } else {
            return $namespaces[$namespace];
        }
    }
}
