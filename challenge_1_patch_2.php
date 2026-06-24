Vulnerable Block
-----------------------
<h3 class="font-bold text-gray-900 text-sm"><?php echo $ticket['title']; ?></h3>
<p class="text-xs text-gray-600 mt-1"><?php echo $ticket['description']; ?></p>



Patch
------------------------
<h3 class="font-bold text-gray-900 text-sm">
    <?php echo htmlspecialchars($ticket['title'], ENT_QUOTES, 'UTF-8'); ?>
</h3>
<p class="text-xs text-gray-600 mt-1">
    <?php echo htmlspecialchars($ticket['description'], ENT_QUOTES, 'UTF-8'); ?>
</p>
