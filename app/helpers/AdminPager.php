<?php
// app/helpers/AdminPager.php
declare(strict_types=1);

final class AdminPager
{
    public const COLS = 3;
    public const PER_PAGE = 45; // 15 ردیف × 3 ستون، مطمئن زیر 100 دکمه
    public const CB_PREFIX_ADMIN = 'ad:';   // انتخاب ادمین
    public const CB_PREFIX_PAGE  = 'pgA:';  // پیجینگ لیست ادمین‌ها
    public const CB_NOOP         = 'noop'; // دکمه‌ی بی‌اثر (پرکننده)

    /**
     * $admins باید آرایه‌ای از ['id'=>..., 'username'=>...] باشد (فقط غیرسودوها)
     */
    public static function keyboard(array $admins, int $page=1, int $perPage=self::PER_PAGE, int $cols=self::COLS): array
    {
        $total = count($admins);
        $pages = max(1, (int)ceil($total / $perPage));
        $page  = max(1, min($page, $pages));
        $slice = array_slice($admins, ($page - 1) * $perPage, $perPage);

        $rows = [];
        foreach (array_chunk($slice, $cols) as $chunk) {
            $row = [];
            foreach ($chunk as $a) {
                $id   = (string)($a['id'] ?? $a['username'] ?? '');
                $text = (string)($a['username'] ?? $id);
                if ($text === '') { $text = '—'; }
                $row[] = [
                    'text' => $text,
                    'callback_data' => self::CB_PREFIX_ADMIN . self::shortenId($id)
                ];
            }
            // برای حفظ گرید سه‌ستونه، ردیف ناقص را با دکمه‌ی بی‌اثر پُر می‌کنیم (اختیاری)
            while (count($row) < $cols) {
                $row[] = ['text' => '·', 'callback_data' => self::CB_NOOP];
            }
            $rows[] = $row;
        }

        // ردیف ناوبری
        $nav = [];
        if ($page > 1)  $nav[] = ['text' => '◀️ قبلی', 'callback_data' => self::CB_PREFIX_PAGE . ($page - 1)];
        $nav[] = ['text' => "صفحه $page/$pages", 'callback_data' => self::CB_NOOP];
        if ($page < $pages) $nav[] = ['text' => 'بعدی ▶️', 'callback_data' => self::CB_PREFIX_PAGE . ($page + 1)];
        $rows[] = $nav;

        return ['inline_keyboard' => $rows];
    }

    public static function parsePageCallback(string $data): ?int
    {
        if (strpos($data, self::CB_PREFIX_PAGE) === 0) {
            return (int)substr($data, strlen(self::CB_PREFIX_PAGE));
        }
        return null;
    }

    public static function isNoop(string $data): bool
    {
        return $data === self::CB_NOOP;
    }

    public static function adminIdFromCallback(string $data): ?string
    {
        if (strpos($data, self::CB_PREFIX_ADMIN) === 0) {
            return self::expandId(substr($data, strlen(self::CB_PREFIX_ADMIN)));
        }
        return null;
    }

    // --- کمک‌ها برای کوتاه نگه داشتن callback_data (<= 64 بایت) ---
    private static function shortenId(string $id): string
    {
        // اگر ID عددی بود، base36 اش می‌کنیم (خیلی کوتاه)
        if ($id !== '' && ctype_digit($id)) {
            return base_convert($id, 10, 36);
        }
        // اگر رشته‌ای بود، 10 بایت از SHA1 را base64url می‌کنیم (غیرقابل بازگشت)
        return rtrim(strtr(substr(base64_encode(substr(hash('sha1', $id, true), 0, 10)), 0, 16), '+/', '-_'), '=');
    }

    private static function expandId(string $short): string
    {
        // فقط برای عددی‌ها قابل برگشت است (base36 -> base10)
        if ($short !== '' && preg_match('/^[0-9a-z]+$/', $short)) {
            $val = base_convert($short, 36, 10);
            if ($val !== null && $val !== '') return (string)$val;
        }
        // برای هش‌ها باید در پروژه نگاشت جدا داشته باشید (در صورت نیاز)
        return $short;
    }
}
