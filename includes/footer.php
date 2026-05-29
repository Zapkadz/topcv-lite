<footer class="bg-white pt-5 pb-3 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="text-success fw-bold">TOPCV LITE</h5>
                <p class="text-muted small">Hệ thống tuyển dụng việc làm hàng đầu. Kết nối ứng viên và nhà tuyển dụng nhanh chóng, hiệu quả.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6 class="fw-bold">Về TopCV Lite</h6>
                <ul class="list-unstyled small text-muted">
                    <li><a href="#" class="text-decoration-none text-muted">Giới thiệu</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Liên hệ</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Bảo mật</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6 class="fw-bold">Hồ sơ & CV</h6>
                <ul class="list-unstyled small text-muted">
                    <li><a href="#" class="text-decoration-none text-muted">Quản lý CV</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Hướng dẫn viết CV</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6 class="fw-bold">Kết nối</h6>
                <div class="d-flex gap-3">
                    <a href="#" class="text-success fs-4"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-success fs-4"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-success fs-4"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <hr>
        <p class="text-center small text-muted">© 2025 TopCV Lite Project. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if(isset($_SESSION['swal_icon'])): ?>
    Swal.fire({
        icon: '<?= $_SESSION['swal_icon'] ?>',
        title: '<?= $_SESSION['swal_title'] ?>',
        showConfirmButton: false, timer: 1500
    });
    <?php unset($_SESSION['swal_icon'], $_SESSION['swal_title']); ?>
    <?php endif; ?>
</script>
</body>
</html>