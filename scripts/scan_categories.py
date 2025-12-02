"""
Quick scanner to review category guesses in storage/app/moka_inventory.csv.
Reads the CSV in 100-row chunks (configurable), applies the same keyword
logic as ProductSeeder::guessCategory, and surfaces items that still fall
into the default SMB bucket.
"""

import argparse
import csv
from collections import Counter, defaultdict
from pathlib import Path
from typing import Iterable, List


# Keep these keyword lists in sync with ProductSeeder::guessCategory
ROK_KEYWORDS: List[str] = [
    "rokok",
    "kretek",
    "sampoerna",
    "marlboro",
    "gudang garam",
    "djarum",
    "esse",
    "magnum",
    "surya",
    "camel",
    "lucky strike",
    "juara",
    "envio",
    "evo",
    "brown",
    "sergio",
    "bull",
    "super",
    "espresso gold",
    "kikaz",
    "samsu",
    "avo",
    "twizz",
    "aroma",
    "clasmild",
    "clas mild",
    "janaka",
    "signature",
    "neslite",
    "la ice",
    "la bold",
    "la lights",
    "la menthol",
]

SKN_KEYWORDS: List[str] = [
    "citra",
    "handbody",
    "shampoo",
    "sabun",
    "odol",
    "bedak",
    "oral",
    "clear",
    "closeup",
    "close up",
    "dove",
    "fair",
    "glow",
    "lifebuoy",
    "lifebouy",
    "lux",
    "pepsodent",
    "peps",
    "makarizo",
    "zinc",
    "sunsilk",
    "ponds",
    "rexona",
    "kahf",
    "vaseline",
    "nivea",
    "sariayu",
    "garnier",
    "palmolive",
    "sari ayu",
    "zwitsal",
    "elips",
    "incidal",
    "lifboy",
    "head shoulders",
    "head&shoulders",
    "head & shoulders",
    "pantene",
    "tresemme",
    "tresemme",
    "treseme",
    "biore",
    "citra h&b",
    "citra h and b",
    "citra h and body",
    "softex",
    "shinzui",
    "sofell",
    "pewangi badan",
    "spray badan",
    "tisu",
    "tissue",
    "mitu",
    "paseo",
    "ciptadent",
    "telon",
    "charm",
    "gatsby",
]

HOM_KEYWORDS: List[str] = [
    "molto",
    "daia",
    "soklin",
    "so klin",
    "easy",
    "rinso",
    "attack",
    "pewangi",
    "softener",
    "detergen",
    "detergent",
    "sunlight",
    "pembersih",
    "pencuci",
    "wipol",
    "bayclin",
    "kapur",
    "kijang",
    "antiseptik",
    "antiseptic",
    "superpell",
    "super pell",
    "pembersih lantai",
    "pembersih kamar mandi",
    "pembersih kaca",
    "pembersih serbaguna",
    "pembersih serba guna",
    "clorox",
    "clorox bleach",
    "clorox pembersih",
    "ekonomi",
    "vixal",
    "downy",
    "raptor",
    "so soft",
]

BEV_KEYWORDS: List[str] = [
    "kopi",
    "coffee",
    "white koffie",
    "nescafe",
    "kapal api",
    "torabika",
    "abc kopi",
    "good day",
    "top coffee",
    "luwak",
    "excelso",
    "latte",
    "cappuccino",
    "mocha",
    "robusta",
    "arabica",
    "teh",
    "tea",
    "sariwangi",
    "sosro",
    "poci",
    "pucuk",
    "teh botol",
    "javana",
    "tong tji",
    "tongtji",
    "nutrisari",
    "nutri sari",
    "flavored drink",
    "sirup",
    "syrup",
    "marjan",
    "abc sirup",
    "frutang",
    "you c1000",
    "youc1000",
    "pocari",
    "hydro coco",
    "mizone",
    "isotonik",
    "isotonic",
    "yakult",
    "lipton",
    "larutan",
    "kaki tiga",
]

MED_KEYWORDS: List[str] = [
    "obat",
    "panadol",
    "paracetamol",
    "parasetamol",
    "ibuprofen",
    "bodrex",
    "komix",
    "woods",
    "konidin",
    "laserine",
    "tolak angin",
    "antangin",
    "mixagrip",
    "promag",
    "entrostop",
    "diapet",
    "cotrimoxazole",
    "betadine",
    "vitamin",
    "vitacimin",
    "imboost",
    "hevit-c",
    "stimuno",
    "marcks",
    "salonpas",
    "counterpain",
    "freshcare",
    "minyak angin",
    "koyo",
    "sirup obat",
    "sanmol",
    "ctm",
    "cetirizine",
    "loratadine",
    "puyer",
    "decolgen",
    "napacin",
    "pilkita",
    "salep",
    "betadine",
]

BBY_KEYWORDS: List[str] = [
    "pampers",
    "diaper",
    "popok",
    "mamy poko",
    "mamypoko",
    "sweety",
    "nepia",
    "huggies",
    "peachy",
    "merries",
    "drypers",
    "goon",
    "goo.n",
    "bebelac",
    "sgm",
    "nutrilon",
    "chil kid",
    "chil school",
    "chilkid",
    "chilschool",
    "frisian baby",
    "morinaga",
    "lactogen",
    "sgm eksplor",
    "frisolac",
    "prenagen",
    "pediasure",
    "neslac",
    "nestle lactogrow",
    "zwitsal baby",
    "cussons baby",
    "johnson",
    "johnson's baby",
    "baby oil",
    "baby lotion",
    "baby bath",
    "baby shampoo",
    "minyak telon",
    "telon",
    "minyak kayu putih",
    "bedak bayi",
    "sabun bayi",
    "tisu basah bayi",
    "tissue basah bayi",
]

# Extra hints to surface misclassified beverages from SMB
BEV_HINTS: List[str] = [
    "hydro coco",
    "coco",
    "you c1000",
    "pocari",
    "mizone",
    "yakult",
]


def guess_category(name: str) -> str:
    lower = name.lower()

    if any(key in lower for key in ["ultra milk", "ultramilk", "susu ultra"]):
        return "SMB"

    if any(key in lower for key in ["larutan", "kaki tiga"]):
        return "BEV"

    staples = [
        "beras",
        "gula",
        "sugar",
        "minyak goreng",
        "minyak sayur",
        "bimoli",
        "filma",
        "sania",
        "sunco",
        "garam",
        "telur",
        "ayam",
        "daging",
        "sapi",
        "tepung",
        "terigu",
        "sagu",
        "kecap",
        "susu",
        "lpg",
        "elpiji",
        "gas",
        "indomie",
        "sarimie",
        "mie sedaap",
        "sedap",
        "sedaap",
        "mihun",
        "mi hun",
        "bihun",
        "mi instan",
        "mie instan",
        "bawang merah",
        "bawang putih",
        "bawang bombay",
        "sarden",
        "sardine",
    ]
    for kw in ROK_KEYWORDS:
        if kw in lower:
            return "ROK"
    for kw in BBY_KEYWORDS:
        if kw in lower:
            return "BBY"
    for kw in SKN_KEYWORDS:
        if kw in lower:
            return "SKN"
    for kw in HOM_KEYWORDS:
        if kw in lower:
            return "HOM"
    for kw in BEV_KEYWORDS:
        if kw in lower:
            return "BEV"
    for kw in MED_KEYWORDS:
        if kw in lower:
            return "MED"
    for kw in staples:
        if kw in lower:
            return "SMB"
    return "MSC"


def iter_chunks(iterable: Iterable, size: int):
    chunk = []
    for item in iterable:
        chunk.append(item)
        if len(chunk) >= size:
            yield chunk
            chunk = []
    if chunk:
        yield chunk


def scan(path: Path, chunk_size: int) -> None:
    with path.open(newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        rows = list(reader)

    total_counts = Counter()
    for chunk_idx, chunk in enumerate(iter_chunks(rows, chunk_size), start=1):
        counts = Counter()
        smb_items = []

        for row in chunk:
            name = (row.get("Items Name (Do Not Edit)") or "").strip()
            cat = guess_category(name)
            counts[cat] += 1
            total_counts[cat] += 1
            if cat == "SMB":
                smb_items.append(name)

        start = (chunk_idx - 1) * chunk_size + 1
        end = start + len(chunk) - 1
        print(f"Chunk {chunk_idx} (rows {start}-{end}) counts: {dict(counts)}")

        if smb_items:
            hints = defaultdict(int)
            for name in smb_items:
                low = name.lower()
                for kw in BEV_HINTS:
                    if kw in low:
                        hints[kw] += 1
            if hints:
                top_hints = sorted(hints.items(), key=lambda x: -x[1])[:5]
                print("  Potential bev hints in SMB:", top_hints)
            # Print a few SMB samples for manual inspection
            for sample in smb_items[:5]:
                print(f"  SMB sample: {sample}")

    print("\nTotal counts:", dict(total_counts))


def main() -> None:
    parser = argparse.ArgumentParser(description="Scan categories from moka_inventory CSV.")
    parser.add_argument(
        "--path",
        default="storage/app/moka_inventory.csv",
        help="Path to CSV (default: storage/app/moka_inventory.csv)",
    )
    parser.add_argument(
        "--chunk-size",
        type=int,
        default=100,
        help="Rows per chunk for reporting (default: 100)",
    )
    args = parser.parse_args()
    scan(Path(args.path), args.chunk_size)


if __name__ == "__main__":
    main()
