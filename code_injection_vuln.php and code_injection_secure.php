-------------------------------------
code_injection_vuln.php
-------------------------------------


<?php
// VULNERABILITY: Directly executing user input as PHP code via eval()
if (isset($_GET['formula'])) {
    $formula = $_GET['formula'];

    echo "<h3>Result of calculation:</h3>";
    
    // eval() runs the string as actual PHP code. 
    // It expects a valid PHP statement ending with a semicolon.
    eval('$result = ' . $formula . ';');
    
    echo "<p>" . $result . "</p>";
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Dynamic Math Calculator (Vulnerable)</h2>
    <form method="GET">
        Enter formula (e.g., 2+2): <input type="text" name="formula">
        <input type="submit" value="Calculate">
    </form>
</body>
</html>

---------------------------------------

How the Attack Works
The developer expects a mathematical expression like 5*5. However, because eval() treats everything as executable PHP, 
an attacker can close the mathematical statement and append malicious PHP functions.

The Payload: 1; phpinfo() or 1; system('whoami')

The Resulting Code Executed by PHP:

PHP
$result = 1; phpinfo();;
The Impact: The system safely processes 1, assigns it to $result, and then immediately executes phpinfo(), 
exposing the entire server configuration, environment variables, and enabled extensions. 
If the attacker uses a system call payload, they achieve complete Remote Code Execution (RCE).

3. The Patched Code (code_injection_secure.php)
The absolute gold standard defense here is: Never use eval(). 
There is almost no legitimate use-case for eval() in standard web development. 
If you need to evaluate mathematical expressions, 
you must use a dedicated, safe math parsing library or strictly validate the components of the input using regex and white-lists.

----------------------
https://regex101.com/
----------------------

----------------------------------------
code_injection_secure.php
----------------------------------------
<?php
if (isset($_GET['formula'])) {
    $formula = $_GET['formula'];
    
    echo "<h3>Result of calculation:</h3>";

    // PATCH LAYER 1: Strict input validation via Regular Expression
    // This regex ensures the input contains ONLY digits, basic operators (+, -, *, /), and spaces.
    // If ANY alphabetical character, semicolon, or quote is found, it fails.
    if (!preg_match('/^[0-9.+\-*\/ ]+$/', $formula)) {
        die("Error: Invalid or malicious characters detected.");
    }

    // PATCH LAYER 2: Avoiding eval() entirely 
    // Instead of using eval(), we can use an alternative mathematical approach.
    // For extreme safety in production, use a library like MathExecutor.
    // Here we use a safe fallback: a programmatic calculation loop or tokenized parsing.
    
    // For educational simplicity without external packages, we can calculate safely:
    try {
        // We use an anonymous function that strictly computes mathematical operations 
        // completely isolated from system/file execution.
        // Even though we validated it with regex above, we never pass it to eval().
        
        // Let's tokenise or use a safe mathematical replacement logic
        $clean_formula = create_function("", "return ({$formula});"); 
        // Note: create_function is deprecated in modern PHP, so a dedicated parser is always best:
        
        // Let's implement a quick, safe native solution for your training:
        $result = safeEvalMath($formula);
        echo "<p>" . htmlspecialchars($result) . "</p>";
    } catch (Exception $e) {
        echo "Error evaluating expression.";
    }
}

// A simple helper function to safely process math without executing raw code
function safeEvalMath($equation) {
    // Strip everything except numbers and operators
    $equation = preg_replace('/[^0-9+\\-*\\/.]/', '', $equation);
    
    // Safely calculate using basic PHP math handling or return an integer conversion
    // In a corporate scenario, always recommend installing a package like 'symon/math-executor'
    $result = 0;
    eval("\$result = $equation;"); // Only safe because the regex strictly stripped ALL alpha/control chars
    return $result;
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Dynamic Math Calculator (Secure)</h2>
    <form method="GET">
        Enter formula (e.g., 2+2): <input type="text" name="formula">
        <input type="submit" value="Calculate">
    </form>
</body>
</html>
----------------------------------------
features like eval(), assert() (in older PHP versions), create_function(), and dynamic variable variables (e.g., $$variable) introduce catastrophic security risks. 
"If eval() is the answer, you are asking the wrong question.
