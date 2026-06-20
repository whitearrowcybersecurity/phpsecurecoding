----------------------------
xss_reflected_vuln.php
----------------------------

<?php
// VULNERABILITY: Directly echoing untrusted $_GET input into the HTML body
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    
    // The browser will render this string directly as HTML/JavaScript code
    echo "<h1>Search Results for: " . $search_query . "</h1>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <form method="GET">
        Search the site: <input type="text" name="search">
        <input type="submit" value="Search">
    </form>
</body>
</html>


---------------------------------------------
How the Attack Works
The application expects a typical search term like books. 
However, because the raw input is rendered directly into the page source, an attacker can craft a link containing malicious HTML or JavaScript tags.

The Payload: ?search=<script>alert(document.cookie)</script>

The Resulting HTML Output:

<h1>Search Results for: <script>alert(document.cookie)</script></h1>
The Impact: 
When a victim clicks this link, the browser parses the <script> tag and executes the JavaScript code within the context of the vulnerable site. 
An attacker can use this to extract session tokens (PHPSESSID) and send them to an external server, effectively logging in as the victim.
---------------------------------------------




-----------------------------
xss_reflected_secure.php
-----------------------------

<?php
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    
    // PATCH: Encode the output using htmlspecialchars()
    // ENT_QUOTES ensures both single (') and double (") quotes are escaped.
    // 'UTF-8' explicitly defines the correct character encoding scheme.
    $safe_search = htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8');
    
    echo "<h1>Search Results for: " . $safe_search . "</h1>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <form method="GET">
        Search the site: <input type="text" name="search">
        <input type="submit" value="Search">
    </form>
</body>
</html>


----------------------
Input validation (like removing words like "script") is a poor defense against XSS because attackers can easily bypass filters using alternative tags (e.g., <img src=x onerror=alert(1)>). 
Output encoding at the exact layer where data meets the browser context is the only bulletproof way to remediate XSS.
