</div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if(isset($_SESSION['swal_icon'])): ?>
    Swal.fire({
        icon: '<?= $_SESSION['swal_icon'] ?>',
        title: '<?= $_SESSION['swal_title'] ?>',
        showConfirmButton: false, timer: 1500,
        customClass: { popup: 'rounded-4' }
    });
    <?php unset($_SESSION['swal_icon'], $_SESSION['swal_title']); ?>
    <?php endif; ?>

    function confirmDelete(url) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: "Dữ liệu sẽ mất vĩnh viễn!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa ngay', cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = url;
        })
    }
</script>
</body>
</html>
<?php ob_end_flush(); ?>