<?php
session_start();

include "language.php";

require "vendor/autoload.php";

function get_unique_identifier()
{
    if (
        isset($_SERVER["HTTP_HOST"]) &&
        substr($_SERVER["HTTP_HOST"], -6) === ".onion"
    ) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    } else {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }
}

function tokenize_multilingual_query($query_string)
{
    $query = trim(strtolower($query_string));

    if (
        strpos($query, " ") === false &&
        preg_match("/[\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}]/u", $query)
    ) {
        return preg_split("//u", $query, -1, PREG_SPLIT_NO_EMPTY);
    } else {
        return array_filter(array_unique(explode(" ", $query)));
    }
}

function get_enhanced_query($original_query)
{
    $enhancer_url = "";
    $data = ["query" => $original_query];
    $options = [
        "http" => [
            "header" => "Content-type: application/json\r\n",
            "method" => "POST",
            "content" => json_encode($data),
            "timeout" => 5,
        ],
    ];
    $context = stream_context_create($options);
    $result_json = @file_get_contents($enhancer_url, false, $context);

    if ($result_json) {
        $result_data = json_decode($result_json, true);
        if (isset($result_data["enhanced_query"])) {
            error_log(
                "Query potenziata da AI: '{$original_query}' -> '{$result_data["enhanced_query"]}'"
            );
            return $result_data["enhanced_query"];
        }
    }
    return $original_query;
}

function parse_boolean_query($query_string)
{
    preg_match_all('/"([^"]+)"/', $query_string, $matches);
    $phrases = $matches[1];
    $query_string = preg_replace('/"([^"]+)"/', "", $query_string);

    $parts = preg_split("/\s+/", $query_string, -1, PREG_SPLIT_NO_EMPTY);
    $query_parts = ["and" => $phrases, "or" => [], "not" => []];
    $current_operator = "and";

    foreach ($parts as $part) {
        $part_lower = strtolower($part);
        if ($part_lower === "or") {
            $current_operator = "or";
            continue;
        }
        if (strpos($part, "-") === 0 && strlen($part) > 1) {
            $query_parts["not"][] = strtolower(substr($part, 1));
            continue;
        }
        $query_parts[$current_operator][] = strtolower($part);
        $current_operator = "and";
    }
    return $query_parts;
}

function tokenize_query_via_service($query_string)
{
    $tokenizer_url = "";

    $data = ["query" => $query_string];

    $options = [
        "http" => [
            "header" => "Content-type: application/json\r\n",

            "method" => "POST",

            "content" => json_encode($data),

            "timeout" => 2,
        ],
    ];

    $context = stream_context_create($options);

    $result_json = @file_get_contents($tokenizer_url, false, $context);

    if ($result_json === false) {
        return array_filter(tokenize_multilingual_query($query_string));
    }

    $result_data = json_decode($result_json, true);

    if (isset($result_data["tokens"])) {
        return $result_data["tokens"];
    }

    return array_filter(array_unique(explode(" ", strtolower($query_string))));
}

$captcha_just_passed = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["captcha_input"])) {
    $user_input = trim(strtolower($_POST["captcha_input"]));

    if (
        isset($_SESSION["captcha_code"]) &&
        $user_input === $_SESSION["captcha_code"]
    ) {
        unset($_SESSION["show_captcha"]);
        unset($_SESSION["captcha_code"]);
        $captcha_just_passed = true;
    } else {
        $_SESSION["captcha_error"] = ${"captcha_error_message" .
            "_" .
            $user_browser_language};
    }
}

$keywords = $_POST["q"] ?? ($_GET["q"] ?? "");
$keywords = htmlspecialchars($keywords);

if (empty($keywords) && isset($_GET["search"]) && !empty($_GET["search"])) {
    $keywords = htmlspecialchars($_GET["search"]);
}

if ($keywords == "") {
    header("Location: ./index.php");
    exit();
}

if ($captcha_just_passed === false) {
    require_once "rate_limiter.php";
}

$limit = 10;

$cache_ttl = 86400;
?>
<!DOCTYPE html>
<html lang="<?php echo $user_browser_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> <?php echo ${"sitesearch" .
        "_" .
        $user_browser_language}; ?> <?php echo $keywords; ?> - <?php echo ${"site_title" .
     "_" .
     $user_browser_language}; ?></title>
    <meta name="robots" content="noindex, follow">
    <link rel="icon" href="./img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="img/apple-touch-icon.png">
    <style>
        :root { --page-bg-color: #f4f7f9; --container-bg-color: #ffffff; --primary-text-color: #2c3e50; --secondary-text-color: #5a6877; --accent-color: #3498db; --accent-color-darker: #2980b9; --input-bg-color: #ffffff; --input-border-color: #dce4ec; --button-text-color: #ffffff; --shadow-color: rgba(44, 62, 80, 0.1); --result-url-color: #27ae60; --result-border-color: #e9edf0; --sidebar-width: 160px; --sidebar-gap: 20px; }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { font-family: 'Roboto', 'Segoe UI', Arial, sans-serif; background-color: var(--page-bg-color); color: var(--primary-text-color); line-height: 1.6; }
        .page-header { background-color: var(--container-bg-color); padding: 15px 20px; box-shadow: 0 2px 10px var(--shadow-color); display: flex; align-items: center; flex-wrap: wrap; gap: 15px; position: sticky; top: 0; z-index: 1000; }
        .header-branding { display: flex; align-items: center; }
        .header-logo { max-height: 40px; width: auto; margin-right: 10px; }
        .header-site-title { font-size: 1.5em; color: var(--accent-color); text-decoration: none; font-weight: 700; }
        .header-site-title:hover { color: var(--accent-color-darker); }
        .header-search-form { display: flex; flex-grow: 1; max-width: 550px; margin: 0 auto; border: 1px solid var(--input-border-color); border-radius: 50px; overflow: hidden; }
        .header-search-input { flex-grow: 1; padding: 10px 20px; font-size: 0.95em; border: none; background-color: var(--input-bg-color); color: var(--primary-text-color); outline: none; }
        .header-search-button { padding: 10px 20px; font-size: 0.95em; background-color: var(--accent-color); color: var(--button-text-color); border: none; cursor: pointer; font-weight: bold; transition: background-color 0.2s ease; }
        .header-search-button:hover { background-color: var(--accent-color-darker); }
        .header-slogan { font-size: 0.8em; color: var(--secondary-text-color); font-style: italic; margin-left: auto; white-space: nowrap; text-align: right; }
        .page-body-container { display: flex; flex-wrap: wrap; max-width: 1200px; margin: 20px auto; padding: 0 15px; gap: var(--sidebar-gap); }
        .main-content { flex-grow: 1; }
        .sidebar { flex-basis: var(--sidebar-width); flex-shrink: 0; }
        .ad-banner-vertical { width: var(--sidebar-width); height: 700px; background-color: var(--page-bg-color); display: flex; align-items: center; justify-content: center; font-size: 0.9em; color: var(--secondary-text-color); text-align: center; padding: 10px; }
        .ad-banner-vertical + .ad-banner-vertical { margin-top: var(--sidebar-gap); }
        .search-info { margin-bottom: 25px; font-size: 0.95em; color: var(--secondary-text-color); }
        .search-info strong { color: var(--primary-text-color); }
        .results-list { list-style: none; padding: 0; }
        .result-item { background-color: var(--container-bg-color); padding: 20px; margin-bottom: 20px; border-radius: 8px; border: 1px solid var(--result-border-color); box-shadow: 0 3px 8px rgba(0,0,0,0.05); animation: fadeInResult 0.5s ease-out forwards; opacity: 0; }
        .result-item:nth-child(1) { animation-delay: 0.1s; }
        .result-item:nth-child(2) { animation-delay: 0.2s; }
        .result-item:nth-child(3) { animation-delay: 0.3s; }
        @keyframes fadeInResult { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .result-title a { font-size: 1em; color: var(--accent-color); text-decoration: none; font-weight: 600; display: block; margin-bottom: 5px; }
        .result-title a:hover { text-decoration: underline; color: var(--accent-color-darker); }
        .result-url { font-size: 0.85em; color: var(--result-url-color); margin-bottom: 8px; word-break: break-all; }
        .result-snippet { font-size: 0.9em; color: var(--secondary-text-color); }
        .result-snippet strong { color: var(--primary-text-color); background-color: #f1c40f20; padding: 0 2px; border-radius: 2px; }
        .no-results { text-align: center; padding: 40px 20px; background-color: var(--container-bg-color); border-radius: 8px; color: var(--secondary-text-color); font-size: 1.1em; }
        .pagination { text-align: center; margin-top: 40px; margin-bottom: 30px; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 15px; margin: 0 4px; border: 1px solid var(--input-border-color); background-color: var(--container-bg-color); color: var(--accent-color); text-decoration: none; border-radius: 4px; transition: background-color 0.2s, color 0.2s; }
        .pagination a:hover { background-color: var(--accent-color); color: var(--button-text-color); border-color: var(--accent-color); }
        .pagination .current-page { background-color: var(--accent-color); color: var(--button-text-color); border-color: var(--accent-color); font-weight: bold; }
        .pagination .disabled { color: #bdc3c7; pointer-events: none; background-color: #f0f3f5; }
        .page-footer { text-align: center; padding: 20px; font-size: 0.85em; color: var(--secondary-text-color); background-color: #e9edf0; margin-top: 40px; clear: both; }
        .page-footer a { color: var(--accent-color); text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
        @media (max-width: 992px) { .page-body-container { flex-direction: column-reverse; } .sidebar { flex-basis: auto; width: 100%; margin-top: var(--sidebar-gap); display: flex; justify-content: center; } .ad-banner-vertical { max-width: 100%; height: auto; min-height: 100px; } }
        @media (max-width: 768px) { .page-header { flex-direction: column; align-items: flex-start; } .header-search-form { width: 100%; margin: 10px 0; } .header-slogan { margin-left: 0; width: 100%; text-align: center; padding-top: 5px; } .result-title a { font-size: 1.15em; } }
    
.ad-result-item {
    background-color: #fffaf0; /* Sfondo leggermente dorato per distinguerlo */
    border: 1px solid #ffeeba;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}
.ad-label {
    font-size: 0.75em;
    font-weight: bold;
    color: #856404;
    border: 1px solid #856404;
    padding: 2px 6px;
    border-radius: 4px;
    margin-right: 10px;
    vertical-align: middle;
}
.ad-result-item .result-title a {
    color: #34495e; /* Colore diverso per il titolo dell'annuncio */
}

/* Style for the GitHub link in the footer */
.footer-github-link {
    display: inline-flex;  /* Aligns icon and text */
    align-items: center;    /* Vertically centers them */
    text-decoration: none;
    color: #555;             /* Or your footer's text color */
    transition: color 0.2s ease;
}

.footer-github-link:hover {
    color: #000;             /* Or your footer's hover color */
    text-decoration: underline;
}

/* Style for the GitHub SVG icon */
.footer-github-link .github-icon {
    width: 16px;              /* Icon size */
    height: 16px;
    margin-right: 5px;        /* Space between icon and text */
    fill: currentColor;       /* Icon will take on the link's color */
}


	</style>
</head>
<body>
    <header class="page-header" role="banner">
       <div class="header-branding"><a href="index.php" title="Back to Homepage"><img src="./img/onionsearchengine.png" alt="Onion Search Engine Logo" class="header-logo"></a><a href="index.php" class="header-site-title"><?php echo ${"site_title" .
           "_" .
           $user_browser_language}; ?></a></div>
        <form action="search.php" method="GET" class="header-search-form" role="search">
            <label for="headerSearchQuery" class="sr-only">Search again</label>
            <input type="text" id="headerSearchQuery" name="q" class="header-search-input" value="<?php echo $keywords; ?>" aria-label="Search field" required>
            <button type="submit" class="header-search-button"><?php echo ${"searchformbutton" .
                "_" .
                $user_browser_language}; ?></button>
        </form>
        <p class="header-slogan"><?php echo ${"slogan" .
            "_" .
            $user_browser_language}; ?> <br> <a href="addurl.php"><?php echo ${"addonionurl" .
     "_" .
     $user_browser_language}; ?></a> &nbsp; <a href="api_plans.php">API</a> &nbsp; <a href="https://drive.onionsearchengine.com" target=_blank>Drive</a> &nbsp; <a href="https://ads.onionsearchengine.com" target=_blank>Ads</a> &nbsp; <a href="map.php?q=<?php echo urlencode(
    $keywords
); ?>"><?php echo ${"onionmap" .
    "_" .
    $user_browser_language}; ?></a> &nbsp; <a href="donation.php"><?php echo ${"donation" .
    "_" .
    $user_browser_language}; ?></a></p>
    </header>

    <div class="page-body-container">
        <!--<aside class="sidebar" role="complementary"><div class="ad-banner-vertical" aria-label="Advertisement"></div></aside>-->
        <main class="main-content" role="main">
            <?php if (
                isset($_SESSION["show_captcha"]) &&
                $_SESSION["show_captcha"]
            ): ?>
                <div class="no-results" style="border: 1px solid #f1c40f; background-color: #f1c40f20; color: #5a6877;">
                    <h3 style="color: #d35400; margin-bottom: 15px;"><?php echo ${"captcha_title" .
                        "_" .
                        $user_browser_language}; ?></h3>
                    <p><?php echo ${"captcha_desc" .
                        "_" .
                        $user_browser_language}; ?></p>
                    <?php if (isset($_SESSION["captcha_error"])): ?>
                        <p style="color: red; font-weight: bold;"><?php echo $_SESSION[
                            "captcha_error"
                        ]; ?></p>
                        <?php unset($_SESSION["captcha_error"]); ?>
                    <?php endif; ?>
                    <form action="search.php" method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="q" value="<?php echo $keywords; ?>">
                        <img src="captcha_image.php" alt="Codice CAPTCHA" style="border: 1px solid #ccc; border-radius: 4px; vertical-align: middle; margin-right: 10px;">
                        <input type="text" name="captcha_input" required autocomplete="off" style="padding: 10px; width: 150px; text-align: center; font-size: 1.2em; vertical-align: middle; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" class="header-search-button" style="vertical-align: middle; margin-left: 10px; border-radius: 4px;"><?php echo ${"captcha_button" .
                            "_" .
                            $user_browser_language}; ?></button>
                    </form>
                </div>
            <?php

                #$keywords = tokenize_multilingual_query($keywords);
                #echo "Attempting to connect to port $port: ";
                #echo "ERROR ($m)\n";

                #$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                #$cache_key = "search_html:" . md5($keywords) . ":page:" . $page;
                #$keywords_str = $_GET['q'] ?? '';
                #$cached_data_json = null;

                #$keywords_str = $_GET['q'] ?? '';
                #$keywords_arr = array_filter(array_unique(explode(' ', strtolower($keywords_str_page))));

                #print_r($keywords_arr);
                #$html_buffer .= '</li>';

                else: ?>
                <p class="search-info" id="search-summary"></p>
		<ul class="results-list" id="results-container">


                    <?php
                    $results_html = null;
                    $cache_is_available = false;
                    $redis = null;
                    $cached_data_json = null;

                    $keywords_str_page = $keywords;

                    $keywords = tokenize_query_via_service($keywords);

                    $keywords_str = $keywords;

                    $obj_r = new Redis();

                    $port = isset($argv[1]) ? $argv[1] : $redis_port;
                    try {
                        $obj_r->connect($redis_host, $port);

                        if (class_exists("Redis")) {
                            try {
                                $redis = new Redis();
                                if (
                                    $redis->connect(
                                        $redis_host,
                                        $redis_port,
                                        1.0
                                    )
                                ) {
                                    if (
                                        $redis->auth([
                                            "user" => $redis_user,
                                            "pass" => $redis_pass,
                                        ])
                                    ) {
                                        $cache_is_available = true;
                                    } else {
                                        error_log(
                                            "Redis authentication failed for user '{$redis_user}'."
                                        );
                                    }
                                }
                            } catch (Throwable $e) {
                                error_log(
                                    "Redis connection failed: " .
                                        $e->getMessage()
                                );
                                $cache_is_available = false;
                            }
                        }
                    } catch (RedisException $ex) {
                        $m = $ex->getMessage();
                        $cache_is_available = false;
                    }

                    if ($cache_is_available) {
                        $page =
                            isset($_GET["page"]) && is_numeric($_GET["page"])
                                ? (int) $_GET["page"]
                                : 1;
                        $cache_key =
                            "search_html:" .
                            md5($keywords_str_page) .
                            ":page:" .
                            $page;
                        $cached_data_json = $redis->get($cache_key);
                    }

                    if ($cached_data_json) {
                        echo "\n";
                        $cached_data = json_decode($cached_data_json, true);
                        $results_html = $cached_data["html"];
                        $totalResults = $cached_data["totalResults"];
                        $totalPages = $cached_data["totalPages"];
                    } else {
                        try {
                            $client = new Client($uri);

                            $db = $client->selectDatabase($databaseName);

                            $indiceCollection = $db->indice;

                            $resultsFound = false;

                            $filter = [];

                            $totalResults = 0;
                            $totalPages = 0;

                            if (stripos($keywords_str_page, "site:") === 0) {
                                $domainToSearch = trim(
                                    substr($keywords_str_page, 5)
                                );
                                if (!empty($domainToSearch)) {
                                    $filter = [
                                        "url" => new Regex(
                                            preg_quote($domainToSearch, "/"),
                                            "i"
                                        ),
                                    ];
                                }
                            } else {
                                $wordIndexCollection = $db->word_index;
                                $keywords_arr = $keywords;

                                $candidate_urls = null;

                                if (!empty($keywords_arr)) {
                                    foreach ($keywords_arr as $keyword) {
                                        $word_doc = $wordIndexCollection->findOne(
                                            ["word" => $keyword]
                                        );
                                        if ($word_doc === null) {
                                            $candidate_urls = [];
                                            break;
                                        }
                                        $urls_for_word = iterator_to_array(
                                            $word_doc->urls
                                        );
                                        if ($candidate_urls === null) {
                                            $candidate_urls = $urls_for_word;
                                        } else {
                                            $candidate_urls = array_intersect(
                                                $candidate_urls,
                                                $urls_for_word
                                            );
                                        }
                                    }
                                }

                                if (!empty($candidate_urls)) {
                                    $filter = [
                                        "url" => [
                                            '$in' => array_values(
                                                $candidate_urls
                                            ),
                                        ],
                                    ];
                                } elseif (!empty($keywords_arr)) {
                                    echo "";
                                    $regex_conditions = [];
                                    foreach ($keywords_arr as $keyword) {
                                        $regex_conditions[] = [
                                            "title" => new Regex(
                                                preg_quote($keyword, "/"),
                                                "i"
                                            ),
                                        ];
                                        $regex_conditions[] = [
                                            "context" => new Regex(
                                                preg_quote($keyword, "/"),
                                                "i"
                                            ),
                                        ];
                                    }
                                    $filter = ['$or' => $regex_conditions];
                                }
                            }

                            if (!empty($filter)) {
                                $page =
                                    isset($_GET["page"]) &&
                                    is_numeric($_GET["page"])
                                        ? (int) $_GET["page"]
                                        : 1;

                                $skip = ($page - 1) * $limit;

                                $totalResults = $indiceCollection->countDocuments(
                                    $filter
                                );

                                $totalPages = ceil($totalResults / $limit);

                                $options = [
                                    "sort" => ["rank" => -1],

                                    "skip" => $skip,

                                    "limit" => $limit,

                                    "projection" => [
                                        "title" => 1,
                                        "context" => 1,
                                        "url" => 1,
                                    ],
                                ];

                                $cursor = $indiceCollection->find(
                                    $filter,
                                    $options
                                );

                                $countadv = 1;

                                $html_buffer = "";

                                $cursor->rewind();

                                while ($cursor->valid()) {
                                    try {
                                        $document = $cursor->current();

                                        $resultsFound = true;
                                        $title = htmlspecialchars(
                                            substr(
                                                $document->title ?? "N/A",
                                                0,
                                                80
                                            ) . "..."
                                        );
                                        $context = htmlspecialchars(
                                            substr(
                                                $document->context ?? "N/A",
                                                0,
                                                100
                                            ) . "..."
                                        );
                                        $url = htmlspecialchars(
                                            substr(
                                                $document->url ?? "N/A",
                                                0,
                                                1000
                                            )
                                        );
                                        $url = str_replace(
                                            ["http://", "https://"],
                                            "",
                                            $url
                                        );

                                        $html_buffer .=
                                            '<li class="result-item">';
                                        $html_buffer .=
                                            '  <h3 class="result-title">';
                                        $html_buffer .=
                                            '    <img src="./img/onionsearchengine.png" alt="Onion Link" height="25px">';
                                        $html_buffer .=
                                            '    <a href="http://' .
                                            $url .
                                            '" target="_blank" >' .
                                            $title .
                                            "</a>";
                                        $html_buffer .= "  </h3>";
                                        $html_buffer .=
                                            '  <p class="result-url">' .
                                            $url .
                                            "</p>";
                                        $html_buffer .=
                                            '  <p class="result-snippet">' .
                                            $context .
                                            "</p>";
                                        $html_buffer .=
                                            '<div class="result-actions" style="margin-top: 10px; text-align: right; ">';
                                        $html_buffer .=
                                            '  <a href="report_page.php?url=' .
                                            urlencode($document->url) .
                                            '" class="report-link" style="font-size: 0.85em; color: #5a6877; text-decoration: none;">Report this result</a>';
                                        $html_buffer .= "</div>";
                                        $html_buffer .= "</li>";

                                        if ($countadv % 3 == 0) {
                                            include "banner-or.php";
                                        }
                                        $countadv = $countadv + 1;
                                    } catch (UnexpectedValueException $e) {
                                        error_log("Error: " . $e->getMessage());
                                    }

                                    $cursor->next();
                                }
                            }

                            if (!$resultsFound) {
                                $html_buffer =
                                    "<div class='no-results'>No results found.</div>";
                            }
                            $results_html = $html_buffer;

                            if (isset($redis)) {
                                $data_to_cache = [
                                    "html" => $results_html,
                                    "totalResults" => $totalResults ?? 0,
                                    "totalPages" => $totalPages ?? 0,
                                ];
                                $redis->set(
                                    $cache_key,
                                    json_encode($data_to_cache),
                                    ["ex" => $cache_ttl]
                                );
                            }
                        } catch (Exception $e) {
                            $results_html =
                                "<div class='no-results'>Database error: " .
                                $e->getMessage() .
                                "</div>";
                        }
                    }

                    echo $ad_html_output;
                    echo $results_html;
                    ?>
                			</ul>
					<?php if (isset($totalPages) && $totalPages > 1):
         if ($totalPages > 10) {
             $totalPages = 10;
         } ?>
                        		<nav class="pagination" aria-label="Search results navigation">
                            		<?php if ($page > 1): ?>
                            			<a href="?q=<?php echo urlencode(
                                   $keywords_str_page
                               ); ?>&page=<?php echo $page -
    1; ?>"  aria-label="Previous page">&laquo; <?php echo ${"searchprev" .
    "_" .
    $user_browser_language}; ?></a>
                            		<?php endif; ?>
                            		<?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                		<a href="?q=<?php echo urlencode(
                                      $keywords_str_page
                                  ); ?>&page=<?php echo $i; ?>" <?php if (
    $i === $page
) {
    echo 'class="current-page"';
} ?>><?php echo $i; ?></a>
                            		<?php endfor; ?>
                            		<?php if ($page < $totalPages): ?>
                                		<a href="?q=<?php echo urlencode(
                                      $keywords_str_page
                                  ); ?>&page=<?php echo $page +
    1; ?>" aria-label="Next page"><?php echo ${"searchnext" .
    "_" .
    $user_browser_language}; ?> &raquo;</a>
                            		<?php endif; ?>
                        		</nav>
                	<?php
     endif; ?>
            <?php endif; ?>
	</main>
	<aside class="sidebar" role="complementary"><div class="ad-banner-vertical" aria-label="Advertisement"><?php include "banner-vr.php"; ?></div></aside>
    </div>
    <footer class="page-footer" role="contentinfo">
	<p>&copy; 2017 - <span id="currentYear">2025</span> <?php echo ${"credit" .
     "_" .
     $user_browser_language}; ?> <a href="mailto:info@onionsearchengine.com" target="_blank"><?php echo ${"contact" .
     "_" .
     $user_browser_language}; ?></a>. <a href="manifesto.php" target="_blank"><?php echo ${"manifesto" .
    "_" .
    $user_browser_language}; ?></a>. <a href="no-log-policy.php" target="_blank"><?php echo ${"nolog" .
    "_" .
    $user_browser_language}; ?></a>.  <a href="https://github.com/onion-search-engine"
   target="_blank"
   rel="noopener noreferrer"
   class="footer-github-link"
   title="Our source code on GitHub (opens in a new tab)">

    <svg class="github-icon"
         xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 16 16"
         fill="currentColor">
        <path d="M8 0c4.42 0 8 3.58 8 8a8.013 8.013 0 0 1-2.34 5.67c-.17.1-.24.05-.24-.1V12.2c0-.43-.15-.72-.44-1.01 1.63-.18 3.34-.8 3.34-3.62 0-.8-.28-1.45-.75-1.95.08-.18.32-.92-.07-1.92 0 0-.62-.2-2.03.75a7.1 7.1 0 0 0-3.62 0c-1.41-.95-2.03-.75-2.03-.75-.4 1-.15 1.74-.07 1.92-.47.5-.75 1.15-.75 1.95 0 2.82 1.71 3.44 3.34 3.62-.3.26-.44.68-.44 1.38v2.13c0 .15-.07.2-.24.1A8.013 8.013 0 0 1 0 8c0-4.42 3.58-8 8-8Z"/>
    </svg>

    <span>Source Code</span>
</a>

</p>
    </footer>
</body>
</html>
