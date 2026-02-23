<?php
// sidebar.php
?>
<div id="sidebar-wrapper">
    <div class="sidebar-heading px-3 py-2 bg-primary text-white fw-bold">
        Traffic System
    </div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php?page=home" class="list-group-item list-group-item-action sidebar-link">
            Dashboard
        </a>
        <a href="dashboard.php?page=add_offense" class="list-group-item list-group-item-action sidebar-link">
            Add Offense
        </a>
        <a href="dashboard.php?page=view_offenses" class="list-group-item list-group-item-action sidebar-link">
            View Offenses
        </a>
        <a href="dashboard.php?page=payments" class="list-group-item list-group-item-action sidebar-link">
            Payments
        </a>
        <a href="logout.php" class="list-group-item list-group-item-action sidebar-link">
            Logout
        </a>
    </div>
</div>


<style>
#wrapper {
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
#sidebar-wrapper {
    width: 250px;   /* slightly wider */
    min-height: 100vh;
    background: #1d3557;
}

/* Content */
#page-content-wrapper {
    flex: 1;
    padding: 30px 40px;
    background: #eef1f5;
}

</style>
