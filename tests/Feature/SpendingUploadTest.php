<?php

namespace Tests\Feature;

use App\Http\Controllers\SpendingHarianController;
use App\Models\Product;
use App\Models\User;
use App\Models\Whitelist;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SpendingUploadTest extends TestCase
{
    private function makeUser(): User
    {
        return User::create([
            'nama' => 'User '.uniqid(),
            'email' => 'upload-'.uniqid().'@example.com',
            'password' => bcrypt('secret'),
            'is_profile_complete' => true,
            'is_active' => true,
        ]);
    }

    private function makeWhitelist(User $owner): Whitelist
    {
        // Kode whitelist produksi numerik tanpa dash (22760, 23643, ...) — penting:
        // parser regional memecah nama produk dengan separator '-', jadi kode yang
        // memuat dash (mis. 'WL-xxx') akan terbelah dan tidak cocok.
        return Whitelist::create([
            'nama' => 'WL Test '.uniqid(),
            'kode' => '20'.substr(uniqid(), -6),
            'platform' => 'facebook',
            'user_id' => $owner->id,
            'tanggal' => now()->format('Y-m-d'),
            'status' => 'aktif',
            'total_topup' => 0,
            'total_spending' => 0,
        ]);
    }

    private function makeProduct(): Product
    {
        $code = 'P'.strtoupper(substr(uniqid(), -6));

        return Product::create([
            'code' => $code,
            'name' => 'Produk Test '.$code,
            'status' => 'active',
            'ad_status' => 'running',
        ]);
    }

    private function tempFile(string $name, string $content): UploadedFile
    {
        // Fake file: mime ditentukan dari ekstensi nama (.csv → text/csv) agar
        // lolos validasi mimes:xlsx,xls,csv (file nyata di-sniff finfo → text/plain).
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Regresi: dulu parseUpload memakai kolom lama products (kode_produk/nama_produk)
     * → SQLSTATE 42S22 "Unknown column 'kode_produk'". Skema baru pakai code/name.
     */
    public function test_parse_upload_works_with_new_products_columns(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $wl = $this->makeWhitelist($user);
        $product = $this->makeProduct();

        $this->actingAs($user);

        // ── File Ads Manager: nama file memuat kode whitelist + tanggal. ──
        // Catatan: JANGAN pakai "s/d" (sampai/dengan) di nama file test — slash di
        // nama file membuat basename() terpotong (file fake disimpan ke temp path).
        $ads = $this->tempFile(
            'OO---'.$wl->kode.'---5-Agu-2026.csv',
            "Nama Kampanye,Jumlah yang dibelanjakan (IDR)\n{$product->code} Campaign,500000\n"
        );

        // ── File regional: kolom product/payment_status/created_at ──
        $regional = $this->tempFile(
            'regional.csv',
            "product,payment_status,created_at\nP.1 - Produk Test {$product->code} - {$wl->kode},paid,2026-08-05 10:00\n"
        );

        $request = Request::create('/spending/parse-upload', 'POST');
        $request->files->set('files', [$ads]);
        $request->files->set('regional', [$regional]);

        $response = (new SpendingHarianController)->parseUpload($request);

        $json = $response->getData(true);
        $this->assertTrue($json['success'], 'parseUpload harus sukses tanpa SQL error');
        $this->assertSame(1, $json['ads_files']);
        $this->assertSame(1, $json['regional_files']);
        $this->assertSame(0, $json['regional_unmatched_count']);

        // Gabungan: tanggal 2026-08-05, spending dari ads + lead/paid dari regional
        $this->assertCount(1, $json['combined']);
        $this->assertSame('2026-08-05', $json['combined'][0]['tanggal']);
        $this->assertSame($wl->kode, $json['combined'][0]['whitelists'][0]['whitelist']['kode']);
        $this->assertSame(500000.0, (float) $json['combined'][0]['whitelists'][0]['products'][0]['spending']);
        $this->assertSame(1, $json['combined'][0]['whitelists'][0]['total_lead']);
        $this->assertSame(1, $json['combined'][0]['whitelists'][0]['total_paid']);
    }

    /**
     * Perubahan pembacaan (11 Agu 2026): kode produk TIDAK harus di awal nama kampanye.
     * Semua advertiser memakai penanda "-" di kiri-kanan kode dengan posisi bebas
     * (contoh: "INIT - 11/8/26 - KBJ - 1"). matchProduct kini membaca nama secara
     * keseluruhan & mencocokkan token utuh yang diapit "-".
     */
    public function test_parse_upload_matches_product_code_anywhere_delimited_by_dash(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $wl = $this->makeWhitelist($user);
        $product = $this->makeProduct();

        $this->actingAs($user);

        // Nama kampanye TIDAK diawali kode — kode berada di tengah, diapit "-".
        $ads = $this->tempFile(
            'OO---'.$wl->kode.'---5-Agu-2026.csv',
            "Nama Kampanye,Jumlah yang dibelanjakan (IDR)\nINIT - 11/8/26 - {$product->code} - 1,500000\n"
        );

        $regional = $this->tempFile(
            'regional.csv',
            "product,payment_status,created_at\nP.1 - Produk Test {$product->code} - {$wl->kode},paid,2026-08-05 10:00\n"
        );

        $request = Request::create('/spending/parse-upload', 'POST');
        $request->files->set('files', [$ads]);
        $request->files->set('regional', [$regional]);

        $response = (new SpendingHarianController)->parseUpload($request);
        $json = $response->getData(true);

        $this->assertTrue($json['success']);
        $this->assertCount(1, $json['combined']);
        $this->assertSame('2026-08-05', $json['combined'][0]['tanggal']);

        // Produk harus ter-cocokkan dengan benar meski kode tidak di awal nama.
        $this->assertCount(1, $json['combined'][0]['whitelists'][0]['products']);
        $this->assertSame(
            $product->name.' ('.$product->code.')',
            $json['combined'][0]['whitelists'][0]['products'][0]['product_name']
        );
        $this->assertSame(500000.0, (float) $json['combined'][0]['whitelists'][0]['products'][0]['spending']);
        $this->assertSame(1, $json['combined'][0]['whitelists'][0]['total_lead']);
        $this->assertSame(1, $json['combined'][0]['whitelists'][0]['total_paid']);
    }

    /**
     * Token kode ber-sufiks varian ("+1.50" dll) di tengah nama juga harus cocok:
     * sufix "+..." dibuang lalu dibandingkan dengan kode master.
     */
    public function test_parse_upload_matches_variant_suffixed_token_in_middle(): void
    {
        $user = $this->makeUser();
        $user->assignRole('advertiser');
        $wl = $this->makeWhitelist($user);
        $product = $this->makeProduct();

        $this->actingAs($user);

        $ads = $this->tempFile(
            'OO---'.$wl->kode.'---5-Agu-2026.csv',
            "Nama Kampanye,Jumlah yang dibelanjakan (IDR)\nINIT - 11/8/26 - {$product->code}+1.50 - 1,500000\n"
        );

        $regional = $this->tempFile(
            'regional.csv',
            "product,payment_status,created_at\nP.1 - Produk Test {$product->code} - {$wl->kode},paid,2026-08-05 10:00\n"
        );

        $request = Request::create('/spending/parse-upload', 'POST');
        $request->files->set('files', [$ads]);
        $request->files->set('regional', [$regional]);

        $response = (new SpendingHarianController)->parseUpload($request);
        $json = $response->getData(true);

        $this->assertTrue($json['success']);
        $this->assertCount(1, $json['combined']);
        $this->assertSame(
            $product->name.' ('.$product->code.')',
            $json['combined'][0]['whitelists'][0]['products'][0]['product_name']
        );
        $this->assertSame(500000.0, (float) $json['combined'][0]['whitelists'][0]['products'][0]['spending']);
    }
}
