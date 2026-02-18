</main>

    <!-- SortableJS (Drag & Drop Library) -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <!-- Admin Common & Page Specific Logic -->
    <script type="module" src="js/admin_common.js"></script>
    <?php if (isset($pageScript)): ?>
        <script type="module" src="js/<?php echo $pageScript; ?>"></script>
    <?php endif; ?>
</body>
</html>
