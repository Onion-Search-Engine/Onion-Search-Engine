<?php

session_start();

include "language.php";
require_once 'rate_limiter.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo ${'title' . '_' . $user_browser_language}; ?></title>
    <meta name="description" content="<?php echo ${'description' . '_' . $user_browser_language}; ?>">
    <meta name="keywords" content="<?php echo ${'keywords' . '_' . $user_browser_language}; ?>">
    <meta name="author" content="Onion Search Engine Team">
    <meta name="robots" content="index, follow"> <link rel="canonical" href="https://onionsearchengine.com/">

    <link rel="icon" href="./img/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="img/onionsearchengine.png"> <meta property="og:title" content="<?php echo ${'title' . '_' . $user_browser_language}; ?>">
    <meta property="og:description" content="<?php echo ${'og_description' . '_' . $user_browser_language}; ?>">
    <meta property="og:image" content="https://onionsearchengine.com/img/onionsearchengine.png"> <meta property="og:url" content="https://onionsearchengine.com/">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo ${'twitter_title' . '_' . $user_browser_language}; ?>">
    <meta name="twitter:description" content="<?php echo ${'twitter_description' . '_' . $user_browser_language}; ?>">
    <meta name="twitter:image" content="https://onionsearchengine.com/img/onionsearchengine.png"> <style>
        :root {
            --page-bg-color: #f4f7f9; /* Light cool gray for page background */
            --container-bg-color: #ffffff; /* White for the main content container */
            --primary-text-color: #2c3e50; /* Dark blue-gray for main text */
            --secondary-text-color: #5a6877; /* Medium gray for slogans, footers */
            --accent-color: #3498db; /* A vibrant, friendly blue */
            --accent-color-darker: #2980b9; /* Darker shade of accent for hover */
            --input-bg-color: #ffffff;
            --input-border-color: #dce4ec; /* Light gray border for inputs */
            --button-text-color: #ffffff;
            --shadow-color: rgba(44, 62, 80, 0.15); /* Softer shadow for light theme */
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body, html {
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            background-color: var(--page-bg-color);
            color: var(--primary-text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 15px;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 700px;
            padding: 30px 40px;
            background-color: var(--container-bg-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px var(--shadow-color);
            animation: fadeInScaleUp 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        @keyframes fadeInScaleUp {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .branding-logo {
            max-width: 110px; /* Adjust to your logo's size */
            height: auto;
            margin-bottom: 15px;
            /* animation: gentleBounce 3s ease-in-out infinite alternate; */
        }
        
        /* Optional subtle animation for logo */
        @keyframes gentleBounce {
            from { transform: translateY(0); }
            to { transform: translateY(-4px); }
        }


        .site-title {
            font-size: clamp(2em, 5vw, 2.8em); /* Responsive title */
            margin-bottom: 8px;
            color: var(--accent-color);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .slogan {
            font-size: clamp(1em, 2.5vw, 1.15em);
            color: var(--secondary-text-color);
            margin-bottom: 35px;
            font-style: italic;
            font-weight: 300;
        }

	.slogan_hidden {
            font-size: clamp(0.9em, 2.5vw, 0.15em);
            color: var(--secondary-text-color);
            margin-bottom: 35px;
            font-style: italic;
            font-weight: 300;
        }


        .sr-only { /* Style to hide label visually but keep for screen readers */
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .search-form {
            display: flex;
            width: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 50px; /* Modern rounded look */
            overflow: hidden;
            border: 1px solid var(--input-border-color); /* Subtle border around the form unit */
            animation: slideUp 0.7s ease-out 0.2s backwards;
        }
         @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-form-input {
            flex-grow: 1;
            padding: 16px 25px;
            font-size: 1.05em;
            border: none; /* Border is on the parent .search-form */
            background-color: var(--input-bg-color);
            color: var(--primary-text-color);
            outline: none;
        }

        .search-form-input::placeholder {
            color: #90a0b1; /* Lighter placeholder text */
            font-weight: 300;
        }

        .search-form-input:focus {
            box-shadow: inset 0 0 0 2px var(--accent-color); /* Focus indicator */
        }

        .search-form-button {
            padding: 16px 28px;
            font-size: 1.05em;
            background-color: var(--accent-color);
            color: var(--button-text-color);
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.25s ease, transform 0.15s ease;
        }

        .search-form-button:hover,
        .search-form-button:focus {
            background-color: var(--accent-color-darker);
            transform: scale(1.02);
        }
        .search-form-button:active {
            transform: scale(0.98);
        }

        .page-footer {
            margin-top: 50px;
            font-size: 0.85em;
            color: var(--secondary-text-color);
            font-weight: 300;
            animation: fadeIn 1s ease-out 0.5s backwards;
        }
        @keyframes fadeIn {
            from { opacity: 0;}
            to { opacity: 1;}
        }

        .page-footer a {
            color: var(--accent-color);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .page-footer a:hover {
            color: var(--accent-color-darker);
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .container {
                padding: 25px 20px;
                margin: 10px;
            }

            .search-form {
                flex-direction: column;
                border-radius: 10px; /* Less rounded on mobile if stacked */
                border: none; /* Remove parent border if stacked */
            }
            .search-form-input, .search-form-button {
                width: 100%;
                border-radius: 8px; /* Round individuals */
                border: 1px solid var(--input-border-color); /* Add border to individuals */
            }
            .search-form-input {
                margin-bottom: 10px; /* Space between input and button */
            }
	}

.top-right-nav {
    position: fixed; 
    top: 15px;       
    right: 20px;      
    z-index: 1000;   
    display: flex;
    align-items: center;
    gap: 15px;
}

.nav-link {
    text-decoration: none;
    color: var(--secondary-text-color);
    font-weight: 600;
    padding: 8px 12px;
    border-radius: 5px;
    background-color: rgba(255, 255, 255, 0.7); 
    backdrop-filter: blur(5px); 
    border: 1px solid rgba(220, 228, 236, 0.5);
    transition: background-color 0.2s, color 0.2s;
}

.nav-link:hover {
    color: var(--primary-text-color);
    background-color: #ffffff;
}

.nav-button-primary {
    background-color: var(--accent-color);
    color: var(--button-text-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.nav-button-primary:hover {
    background-color: var(--accent-color-darker);
    color: var(--button-text-color);
}

.captcha-container {
            width: 100%;
            max-width: 500px;
            padding: 30px 40px;
            background-color: #ffffff;
            border: 1px solid #f1c40f;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(44, 62, 80, 0.15);
        }
        .captcha-container h3 { color: #d35400; }


.apps-promo-section {
    margin-top: 40px;
    padding: 30px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    width: 100%;
    max-width: 700px; 
}
.apps-promo-section h2 {
    color: var(--primary-text-color);
    font-size: 1.8em;
    margin-top: 0;
    margin-bottom: 10px;
}
.apps-promo-section p {
    color: var(--secondary-text-color);
    max-width: 500px;
    margin: 0 auto 30px auto;
}
.store-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.store-button img {
    height: 60px; 
    transition: transform 0.2s ease-in-out;
}
.store-button:hover img {
    transform: scale(1.05);
}


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

 <nav class="top-right-nav">
	<a href="solutions.php" class="nav-link">Platform</a>
	<a href="./support/"  class="nav-link">Support</a>
	<a href="https://onionsearchengine.com/blog" target=_blank  class="nav-link">Blog</a>
	<a href="https://ads.onionsearchengine.com/blog" target=_blank  class="nav-link">Ads</a>
	<a href="https://drive.onionsearchengine.com/" target=_blank  class="nav-link">Drive</a>
	<a href="https://onionmail.org" target=_blank  class="nav-link">Mail</a>
	<a href="https://torscape.onionsearchengine.com/" target=_blank  class="nav-link">Torscape</a>
	<a href="about.php"  class="nav-link">About Us</a>
        <a href="login.php" target=_blank class="nav-link">Log In</a>
	<a href="signup.php" target=_blank class="nav-link nav-button-primary">Sign Up</a>
    </nav>


<?php if (isset($_SESSION['show_captcha']) && $_SESSION['show_captcha']): ?>
    
        <div class="captcha-container">
            <h3><?php echo ${'captcha_title' . '_' . $user_browser_language}; ?></h3>
            <p><?php echo ${'captcha_desc' . '_' . $user_browser_language}; ?></p>
            
            <?php if (isset($_SESSION['captcha_error'])): ?>
                <p style="color: red; font-weight: bold;"><?php echo $_SESSION['captcha_error']; ?></p>
                <?php unset($_SESSION['captcha_error']); ?>
            <?php endif; ?>

            <form action="search.php" method="POST" style="margin-top: 20px;" class="search-form">
                <input type="hidden" name="q" value="">
                <img src="captcha_image.php" alt="Codice CAPTCHA" style="vertical-align: middle; margin-right: 10px;">
                <input type="text" name="captcha_input" class="search-form-input" required autocomplete="off">
                <button type="submit" class="search-form-button"><?php echo ${'captcha_button' . '_' . $user_browser_language}; ?></button>
            </form>
        </div>

    <?php else: ?>

    <div class="container">
        <header role="banner">
            <img src="./img/onionsearchengine.png" alt="Onion Search Engine Logo" class="branding-logo">
            <h1 class="site-title"><?php echo ${'site_title' . '_' . $user_browser_language}; ?></h1>
	    <p class="slogan"><?php echo ${'slogan' . '_' . $user_browser_language}; ?></p>
	    <p class="slogan_hidden">Hidden: <a href="http://37djtvjcpiprohcrlyvlhfil45kdlfizsyvilqskgvdrafn5mocz4cid.onion" target=_blank>37djtvjcpiprohcrlyvlhfil45kdlfizsyvilqskgvdrafn5mocz4cid.onion</a></p>
        </header>

        <main role="main">
            <form action="search.php" method="GET" class="search-form" role="search" aria-labelledby="search-form-title">
                <h2 id="search-form-title" class="sr-only"><?php echo ${'search_form_title' . '_' . $user_browser_language}; ?></h2>
                <label for="searchQuery" class="sr-only"><?php echo ${'searchQuery' . '_' . $user_browser_language}; ?></label>
                <input type="text" id="searchQuery" name="q" class="search-form-input" placeholder="<?php echo ${'searchforminput' . '_' . $user_browser_language}; ?>" aria-label="Search field" required>
                <button type="submit" class="search-form-button"><?php echo ${'searchformbutton' . '_' . $user_browser_language}; ?></button>
            </form>
        </main>
    </div>

	<section class="apps-promo-section">
    <h2>Take Onion Search Engine With You</h2>
    <p>Access our privacy-first search on the go with our Android App or directly in your browser with our Firefox Add-on.</p>
    <div class="store-buttons">
        <a href="https://play.google.com/store/apps/details?id=com.onionsearchengine.onionsearchengine" target="_blank" class="store-button google-play">
            <img src="img/en_badge_web_generic.png" alt="Get it on Google Play">
        </a>
        <a href="https://addons.mozilla.org/it/firefox/addon/onion-search-engine/" target="_blank" class="store-button firefox">
            <img src="img/get-the-addon-fx-apr-2020.svg" alt="Get the Firefox Add-on">
        </a>
    </div>
</section>

    <footer role="contentinfo" class="page-footer">
        <p>&copy; 2017 - <span id="currentYear">2025</span> <?php echo ${'credit' . '_' . $user_browser_language}; ?> <a href="mailto:info@onionsearchengine.com" target=_blank><?php echo ${'contact' . '_' . $user_browser_language}; ?></a>.  <a href="manifesto.php" target=_blank><?php echo ${'manifesto' . '_' . $user_browser_language}; ?></a>. <a href="no-log-policy.php" target=_blank><?php echo ${'nolog' . '_' . $user_browser_language}; ?></a>. <a href="https://github.com/onion-search-engine" 
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

<!--    <script>
        try {
            document.getElementById('currentYear').textContent = new Date().getFullYear();
        } catch (e) {
            // Fallback in case JS is disabled or fails,
            // the hardcoded year in the HTML is already present.
            console.info("JavaScript for year update is non-essential.");
        }
    </script>-->
    <?php endif; ?>


</body>
</html>
