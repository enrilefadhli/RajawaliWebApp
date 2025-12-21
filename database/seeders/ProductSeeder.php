<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SplFileObject;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryMap = [
            'SMB' => Category::firstOrCreate(['category_code' => 'SMB'], ['category_name' => 'Sembako'])->id,
            'SKN' => Category::firstOrCreate(['category_code' => 'SKN'], ['category_name' => 'Perawatan Pribadi'])->id,
            'ROK' => Category::firstOrCreate(['category_code' => 'ROK'], ['category_name' => 'Rokok'])->id,
            'HOM' => Category::firstOrCreate(['category_code' => 'HOM'], ['category_name' => 'Kebutuhan Rumah Tangga'])->id,
            'BEV' => Category::firstOrCreate(['category_code' => 'BEV'], ['category_name' => 'Beverages'])->id,
            'MSC' => Category::firstOrCreate(['category_code' => 'MSC'], ['category_name' => 'Miscellaneous'])->id,
            'BBY' => Category::firstOrCreate(['category_code' => 'BBY'], ['category_name' => 'Baby Care'])->id,
        ];

        $products = [
            ['sku' => '8999999012625', 'name' => 'BANGO 220 ML', 'price' => 10000, 'variant' => 'PCS', 'cat' => 'SMB'],
            ['sku' => '8999999204488', 'name' => 'BANGO 220 ML', 'price' => 215000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999533496', 'name' => 'BANGO MANIS SAK 1000', 'price' => 10000, 'variant' => 'LUSINAN', 'cat' => 'SMB'],
            ['sku' => '8999999204464', 'name' => 'BANGO MANIS SAK 1000', 'price' => 115000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999514006', 'name' => 'BANGO MANIS 77 GR', 'price' => 2500, 'variant' => null, 'cat' => 'SMB'],
            ['sku' => '8999999204471', 'name' => 'BANGO MANIS 77 GR', 'price' => 115000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999100506', 'name' => 'BANGO MANIS RL 700 GR', 'price' => 22500, 'variant' => null, 'cat' => 'SMB'],
            ['sku' => '8999999210748', 'name' => 'BANGO MANIS RL 700 GR', 'price' => 255000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999003777', 'name' => 'CITRA H&B FRESH RADIANCE 250 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B FRESH RADIANCE 250 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003517', 'name' => 'CITRA H&B FRESH RADIANCE 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B FRESH RADIANCE 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003661', 'name' => 'CITRA H&B LASTING GLOW 120 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 120 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003524', 'name' => 'CITRA H&B LASTING GLOW 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999718077', 'name' => 'CITRA H&B LASTING GLOW 9 ML/480', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 9 ML/480', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003920', 'name' => 'CITRA H&B LASTING GLOW SCHT 9 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW SCHT 9 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
        ];

        $counters = [];

        $nextCode = function (string $prefix) use (&$counters) {
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;
            return sprintf('%s%05d', $prefix, $counters[$prefix]);
        };

        $file = storage_path('app/moka_inventory.csv');
        if (file_exists($file)) {
            $rows = $this->readCsv($file, 5000);
            if ($rows) {
                $products = $rows;
            }
        }

        foreach ($products as $item) {
            $purchasePrice = (int) $item['price'];
            $sellingPrice = $purchasePrice > 0 ? (int) ceil($purchasePrice * 1.15) : 0;
            $catId = $categoryMap[$item['cat']] ?? null;

            if (! $catId) {
                continue;
            }

            $product = Product::firstOrNew(
                $item['sku'] ? ['sku' => $item['sku']] : ['product_name' => $item['name'], 'variant' => $item['variant']]
            );

            if (! $product->exists) {
                $product->product_code = $nextCode($item['cat']);
            }

            $product->fill([
                'category_id' => $catId,
                'sku' => $item['sku'],
                'product_name' => $item['name'],
                'variant' => $item['variant'],
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'discount_percent' => null,
                'discount_amount' => null,
                'minimum_stock' => 10,
                'status' => 'DISABLED',
            ]);

            $product->save();
        }
    }

    protected function readCsv(string $path, int $limit = 100): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(',');

        $headers = [];
        $data = [];

        foreach ($file as $index => $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if (empty($headers)) {
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                continue;
            }

            $rowAssoc = [];
            foreach ($headers as $i => $key) {
                $rowAssoc[$key] = $row[$i] ?? null;
            }

            $sku = trim((string) ($rowAssoc['sku'] ?? ''));
            $name = trim((string) ($rowAssoc['items name (do not edit)'] ?? ''));
            $variant = $this->normalizeVariant(trim((string) ($rowAssoc['variant name'] ?? '')));
            $priceRaw = $rowAssoc['basic - price'] ?? 0;
            $price = (int) preg_replace('/\D/', '', (string) $priceRaw);

            if ($name === '') {
                continue;
            }

            $cat = $this->guessCategory($name);

            $data[] = [
                'sku' => $sku !== '' ? $sku : null,
                'name' => $name,
                'price' => $price,
                'variant' => $variant,
                'cat' => $cat,
            ];

            if (count($data) >= $limit) {
                break;
            }
        }

        return $data;
    }

    public function guessCategory(string $name): string
    {
        $lower = Str::lower($name);

        // Guards for ambiguous terms
        if (Str::contains($lower, ['ultra milk', 'ultramilk', 'susu ultra'])) {
            return 'SMB';
        }
        if (Str::contains($lower, ['larutan', 'kaki tiga'])) {
            return 'BEV';
        }

        // Tobacco
        if (Str::contains($lower, [
            'rokok', 'kretek', 'sampoerna', 'marlboro', 'gudang garam', 'djarum', 'esse', 'magnum', 'surya', 'camel',
            'lucky strike', 'juara', 'envio', 'evo', 'brown', 'sergio', 'bull', 'super', 'espresso gold', 'kikaz', 'samsu',
            'avo', 'twizz', 'aroma', 'clasmild', 'clas mild', 'janaka', 'signature', 'neslite', 'la ice', 'la bold', 'la lights', 'la menthol'
        ])) {
            return 'ROK';
        }

        // Baby care
        if (Str::contains($lower, [
            'pampers', 'diaper', 'popok', 'mamy poko', 'mamypoko', 'sweety', 'nepia', 'huggies', 'peachy', 'merries', 'drypers', 'goon', 'goo.n',
            'bebelac', 'sgm', 'nutrilon', 'chil kid', 'chil school', 'chilkid', 'chilschool', 'frisian baby', 'morinaga', 'lactogen',
            'sgm eksplor', 'frisolac', 'prenagen', 'pediasure', 'neslac', 'nestle lactogrow',
            'zwitsal baby', 'cussons baby', 'johnson', "johnson's baby", 'baby oil', 'baby lotion', 'baby bath', 'baby shampoo',
            'minyak telon', 'telon', 'minyak kayu putih', 'bedak bayi', 'sabun bayi', 'tisu basah bayi', 'tissue basah bayi'
        ])) {
            return 'BBY';
        }

        // Personal care
        if (Str::contains($lower, [
            'citra', 'handbody', 'shampoo', 'sabun', 'odol', 'bedak', 'oral', 'clear', 'closeup', 'close up', 'dove', 'fair', 'glow',
            'lifebuoy', 'lifebouy', 'lux', 'pepsodent', 'peps', 'sunsilk', 'ponds', 'rexona', 'kahf', 'vaseline', 'nivea', 'sariayu', 'garnier', 'makarizo', 'zinc',
            'palmolive', 'sari ayu', 'zwitsal', 'elips', 'incidal', 'lifboy', 'head shoulders', 'head&shoulders',
            'head & shoulders', 'pantene', 'tresemme', 'tresemme', 'treseme', 'biore', 'citra h&b', 'citra h and b',
            'citra h and body', 'softex', 'shinzui', 'sofell', 'pewangi badan', 'spray badan','valeno', 'tisu', 'tissue',
            'mitu', 'paseo', 'ciptadent', 'telon', 'charm', 'gatsby'
        ])) {
            return 'SKN';
        }

        // Household
        if (Str::contains($lower, [
            'molto', 'daia', 'soklin', 'so klin', 'easy', 'rinso', 'attack', 'pewangi', 'softener', 'detergen',
            'detergent', 'sunlight', 'pembersih', 'pencuci', 'wipol', 'bayclin', 'kapur', 'kijang', 'antiseptik',
            'antiseptic', 'superpell', 'super pell', 'pembersih lantai', 'pembersih kamar mandi', 'pembersih kaca',
            'pembersih serbaguna', 'pembersih serba guna', 'clorox', 'clorox bleach', 'clorox pembersih', 'ekonomi',
            'vixal', 'downy', 'raptor', 'so soft'
        ])) {
            return 'HOM';
        }

        // Beverages
        if (Str::contains($lower, [
            'kopi', 'coffee', 'white koffie', 'nescafe', 'kapal api', 'torabika', 'abc kopi', 'good day', 'top coffee',
            'luwak', 'excelso', 'latte', 'cappuccino', 'mocha', 'robusta', 'arabica',
            'teh', 'tea', 'sariwangi', 'sosro', 'poci', 'pucuk', 'teh botol', 'javana', 'tong tji', 'tongtji',
            'nutrisari', 'nutri sari', 'flavored drink', 'sirup', 'syrup', 'marjan', 'abc sirup', 'frutang',
            'you c1000', 'youc1000', 'pocari', 'hydro coco', 'mizone', 'isotonik', 'isotonic',
            'yakult', 'lipton', 'larutan', 'kaki tiga'
        ])) {
            return 'BEV';
        }

        // Medicines
        if (Str::contains($lower, [
            'obat', 'panadol', 'paracetamol', 'parasetamol', 'ibuprofen', 'bodrex', 'komix', 'woods', 'konidin', 'laserine',
            'tolak angin', 'antangin', 'mixagrip', 'promag', 'entrostop', 'diapet', 'cotrimoxazole', 'betadine',
            'vitamin', 'vitacimin', 'imboost', 'hevit-c', 'stimuno', 'marcks', 'salonpas', 'counterpain', 'freshcare',
            'minyak angin', 'koyo', 'sirup obat', 'sanmol', 'ctm', 'cetirizine', 'loratadine','puyer', 'decolgen', 'napacin', 'pilkita', 'salep', 'betadine'
        ])) {
            return 'MED';
        }

        // Staples (Sembako)
        if (Str::contains($lower, [
            'beras', 'gula', 'sugar', 'minyak goreng', 'minyak sayur', 'bimoli', 'filma', 'sania', 'sunco', 'garam',
            'telur', 'ayam', 'daging', 'sapi', 'tepung', 'terigu', 'sagu', 'kecap', 'susu', 'lpg', 'elpiji', 'gas',
            'indomie', 'sarimie', 'mie sedaap', 'sedap', 'sedaap', 'mihun', 'mi hun', 'bihun', 'mi instan', 'mie instan',
            'bawang merah', 'bawang putih', 'bawang bombay','ultra','uht', 'sarden', 'sardine'
        ])) {
            return 'SMB';
        }

        return 'MSC';
    }

    protected function normalizeVariant(?string $variant): ?string
    {
        if ($variant === null || $variant === '') {
            return null;
        }

        $upper = Str::upper($variant);
        if ($upper === 'PICS') {
            return 'PCS'; // normalize typo
        }

        $lower = Str::lower($variant);
        if (Str::contains($lower, 'dus kecil') || Str::contains($lower, 'dus besar')) {
            return 'DUS'; // unify dus variants
        }

        return $variant;
    }
}
