Vulnerable Block
----------------------------------
$admin_status_notice = false;
if ($_SESSION['role'] = 'admin') { 
    $admin_status_notice = true;
}



Patch
----------------------------------
$admin_status_notice = false;
// Fix: We test for evaluation equivalence, preventing session state modification
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { 
    $admin_status_notice = true;
}
