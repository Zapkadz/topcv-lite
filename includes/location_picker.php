<?php
/**
 * Dropdown địa điểm: đóng mặc định (giống select Ngành nghề), mở khi click — có ô tìm + cuộn.
 *
 * @param array<int, array{id: int|string, name: string}> $locations
 */
function location_picker_render(
    array $locations,
    ?int $selectedId = null,
    string $inputName = 'location_id',
    string $label = 'Địa điểm làm việc',
    string $placeholder = '-- Chọn khu vực --'
): void
{
    static $assetsLoaded = false;
    $baseUrl = '/topcv_lite/';

    $selectedId = $selectedId ? (int) $selectedId : null;
    $selectedName = '';
    foreach ($locations as $loc) {
        if ($selectedId !== null && (int) $loc['id'] === $selectedId) {
            $selectedName = $loc['name'];
            break;
        }
    }

    if (!$assetsLoaded) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($baseUrl) . 'assets/css/location-picker.css">' . "\n";
        echo '<script src="' . htmlspecialchars($baseUrl) . 'assets/js/location-picker.js" defer></script>' . "\n";
        $assetsLoaded = true;
    }

    $displayLabel = $selectedName !== '' ? $selectedName : $placeholder;
    $isPlaceholder = $selectedName === '';
    ?>
    <div class="location-picker" data-location-picker data-placeholder="<?= htmlspecialchars($placeholder) ?>">
        <label class="form-label"><?= htmlspecialchars($label) ?> <span class="text-danger">*</span></label>
        <div class="location-picker-control">
            <button type="button"
                    class="form-select location-picker-toggle text-start"
                    aria-haspopup="listbox"
                    aria-expanded="false">
                <span class="location-picker-label<?= $isPlaceholder ? ' is-placeholder' : '' ?>"><?= htmlspecialchars($displayLabel) ?></span>
            </button>
            <input type="hidden"
                   name="<?= htmlspecialchars($inputName) ?>"
                   value="<?= $selectedId !== null ? (int) $selectedId : '' ?>"
                   required>
            <div class="location-picker-panel" hidden>
                <input type="text"
                       class="form-control form-control-sm location-picker-search"
                       placeholder="Gõ tên tỉnh/thành để tìm..."
                       autocomplete="off">
                <div class="location-picker-list" role="listbox" aria-label="Danh sách địa điểm">
                    <?php foreach ($locations as $loc): ?>
                        <button type="button"
                                class="location-picker-option<?= $selectedId === (int) $loc['id'] ? ' is-active' : '' ?>"
                                data-id="<?= (int) $loc['id'] ?>"
                                data-name="<?= htmlspecialchars($loc['name']) ?>">
                            <?= htmlspecialchars($loc['name']) ?>
                        </button>
                    <?php endforeach; ?>
                    <div class="location-picker-empty d-none">Không tìm thấy địa điểm phù hợp.</div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
