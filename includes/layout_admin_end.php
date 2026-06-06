    </div><!-- /page-body -->
</div><!-- /main-content -->

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar  = document.querySelector('.sidebar');
const overlay  = document.getElementById('sidebarOverlay');
const toggleBtn = document.getElementById('sidebarToggle');

function openSidebar() {
    sidebar.classList.add('show');
    overlay.classList.add('show');
}

function closeSidebar() {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
}

toggleBtn?.addEventListener('click', () => {
    sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
});

overlay.addEventListener('click', closeSidebar);
</script>
</body>
</html>
