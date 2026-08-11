export const DISCOUNT_RATE = 0.03;
export const POLEP_PRICE = 100;
export const VAT_RATE = 23;

export interface VehicleOffer {
  slug: string;
  title: string;
  size?: string;
  image: string;
  imageAlt: string;
  note?: string;
  prices: { period: string; sourcePrice: number }[];
}

export interface RentalOffer {
  title: string;
  period: string;
  sourcePrice: number;
}

export interface StreetCategory {
  code: "A" | "B" | "C";
  items: string[];
}

export function discountPrice(sourcePrice: number): number {
  return Math.round((sourcePrice * (1 - DISCOUNT_RATE) + Number.EPSILON) * 100) / 100;
}

export function formatEuro(price: number): string {
  return `${price.toLocaleString("sk-SK", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} €`;
}

const oneVisionNote = "Pri aplikácii na okná sa používa one-vision fólia.";

export const BUS_OFFERS: VehicleOffer[] = [
  {
    slug: "celoplosny-polep-kratky-autobus",
    title: "Celoplošný polep — krátky autobus",
    image: "/assets/mhd-kosice/celoplosny-polep-kratky-autobus.webp",
    imageAlt: "Celoplošný polep krátkeho autobusu v Košiciach",
    prices: [
      { period: "1 mesiac", sourcePrice: 720 },
      { period: "3 mesiace", sourcePrice: 612 },
      { period: "6 mesiacov", sourcePrice: 576 },
      { period: "12 mesiacov", sourcePrice: 540 },
    ],
  },
  {
    slug: "celoplosny-polep-dlhy-autobus",
    title: "Celoplošný polep — dlhý autobus",
    image: "/assets/mhd-kosice/celoplosny-polep-dlhy-autobus.webp",
    imageAlt: "Celoplošný polep dlhého autobusu v Košiciach",
    prices: [
      { period: "1 mesiac", sourcePrice: 820 },
      { period: "3 mesiace", sourcePrice: 697 },
      { period: "6 mesiacov", sourcePrice: 656 },
      { period: "12 mesiacov", sourcePrice: 615 },
    ],
  },
  {
    slug: "busboard-xxl",
    title: "Busboard XXL",
    size: "do 386 × 210 cm",
    image: "/assets/mhd-kosice/busboard-xxl.webp",
    imageAlt: "Reklamná plocha Busboard XXL na autobuse",
    prices: [
      { period: "1 mesiac", sourcePrice: 450 },
      { period: "3 mesiace", sourcePrice: 382.5 },
      { period: "6 mesiacov", sourcePrice: 360 },
      { period: "12 mesiacov", sourcePrice: 337.5 },
    ],
  },
  {
    slug: "busboard-xl",
    title: "Busboard XL",
    size: "do 255 × 210 cm",
    image: "/assets/mhd-kosice/busboard-xl.webp",
    imageAlt: "Reklamná plocha Busboard XL na autobuse",
    prices: [
      { period: "1 mesiac", sourcePrice: 370 },
      { period: "3 mesiace", sourcePrice: 314.5 },
      { period: "6 mesiacov", sourcePrice: 296 },
      { period: "12 mesiacov", sourcePrice: 277.5 },
    ],
  },
  {
    slug: "busboard-l",
    title: "Busboard L",
    size: "do 127 × 210 cm",
    image: "/assets/mhd-kosice/busboard-l.webp",
    imageAlt: "Reklamná plocha Busboard L na autobuse",
    prices: [
      { period: "1 mesiac", sourcePrice: 180 },
      { period: "3 mesiace", sourcePrice: 153 },
      { period: "6 mesiacov", sourcePrice: 144 },
      { period: "12 mesiacov", sourcePrice: 135 },
    ],
  },
  {
    slug: "doorboard",
    title: "Doorboard",
    size: "podľa typu vozidla",
    image: "/assets/mhd-kosice/doorboard.webp",
    imageAlt: "Reklamná plocha Doorboard na autobuse",
    prices: [
      { period: "1 mesiac", sourcePrice: 180 },
      { period: "3 mesiace", sourcePrice: 153 },
      { period: "6 mesiacov", sourcePrice: 144 },
      { period: "12 mesiacov", sourcePrice: 135 },
    ],
  },
  {
    slug: "bocna-samolepka-220x60",
    title: "Bočná samolepka",
    size: "do 220 × 60 cm (1,32 m²)",
    image: "/assets/mhd-kosice/bocna-samolepka-220x60.webp",
    imageAlt: "Bočná reklamná samolepka na autobuse",
    note: oneVisionNote,
    prices: [
      { period: "1 mesiac", sourcePrice: 100 },
      { period: "2 mesiace", sourcePrice: 95 },
      { period: "3 mesiace", sourcePrice: 90 },
      { period: "6 mesiacov", sourcePrice: 85 },
      { period: "12 mesiacov", sourcePrice: 80 },
    ],
  },
  {
    slug: "bocna-samolepka-150x30",
    title: "Bočná samolepka",
    size: "do 150 × 30 cm (0,45 m²)",
    image: "/assets/mhd-kosice/bocna-samolepka-150x30.webp",
    imageAlt: "Bočná reklamná samolepka na autobuse",
    note: oneVisionNote,
    prices: [
      { period: "1 mesiac", sourcePrice: 60 },
      { period: "2 mesiace", sourcePrice: 57 },
      { period: "3 mesiace", sourcePrice: 54 },
      { period: "6 mesiacov", sourcePrice: 51 },
      { period: "12 mesiacov", sourcePrice: 48 },
    ],
  },
  {
    slug: "bocna-samolepka-130x30",
    title: "Bočná samolepka",
    size: "do 130 × 30 cm (0,39 m²)",
    image: "/assets/mhd-kosice/bocna-samolepka-130x30.webp",
    imageAlt: "Bočná reklamná samolepka na autobuse",
    note: oneVisionNote,
    prices: [{ period: "1 mesiac", sourcePrice: 30 }],
  },
  {
    slug: "zadna-plocha-okno",
    title: "Zadná plocha — okno",
    image: "/assets/mhd-kosice/zadna-plocha-okno.webp",
    imageAlt: "Zadná okenná reklamná plocha autobusu",
    prices: [
      { period: "1 mesiac", sourcePrice: 100 },
      { period: "3 mesiace", sourcePrice: 90 },
      { period: "6 mesiacov", sourcePrice: 85 },
      { period: "12 mesiacov", sourcePrice: 80 },
    ],
  },
  {
    slug: "zadna-plocha-cela",
    title: "Zadná plocha — celá",
    image: "/assets/mhd-kosice/zadna-plocha-cela.webp",
    imageAlt: "Celá zadná reklamná plocha autobusu",
    prices: [
      { period: "1 mesiac", sourcePrice: 200 },
      { period: "3 mesiace", sourcePrice: 180 },
      { period: "6 mesiacov", sourcePrice: 170 },
      { period: "12 mesiacov", sourcePrice: 160 },
    ],
  },
];

export const TRAM_OFFERS: VehicleOffer[] = [
  {
    slug: "celoplosny-polep-elektricka",
    title: "Celoplošný polep — električka",
    image: "/assets/mhd-kosice/celoplosny-polep-elektricka.webp",
    imageAlt: "Celoplošný polep električky v Košiciach",
    note: "Maximálny povolený obsah grafiky na oknách je 10 %.",
    prices: [
      { period: "1 mesiac", sourcePrice: 920 },
      { period: "3 mesiace", sourcePrice: 782 },
      { period: "6 mesiacov", sourcePrice: 736 },
      { period: "12 mesiacov", sourcePrice: 690 },
    ],
  },
  {
    slug: "e-board-xxl",
    title: "E-board XXL",
    size: "400 × 200 cm",
    image: "/assets/mhd-kosice/e-board-xxl.webp",
    imageAlt: "Reklamná plocha E-board XXL na električke",
    prices: [
      { period: "1 mesiac", sourcePrice: 550 },
      { period: "3 mesiace", sourcePrice: 467.5 },
      { period: "6 mesiacov", sourcePrice: 440 },
      { period: "12 mesiacov", sourcePrice: 412.5 },
    ],
  },
];

export const SIT_RENTALS: RentalOffer[] = [
  { title: "TV A", period: "1 mesiac", sourcePrice: 31 },
  { title: "TV B", period: "1 mesiac", sourcePrice: 28.5 },
  { title: "VO A", period: "1 mesiac", sourcePrice: 21.5 },
  { title: "VO B", period: "1 mesiac", sourcePrice: 20.5 },
  { title: "VO C", period: "1 mesiac", sourcePrice: 11.5 },
];

export const FLAG_RENTALS: RentalOffer[] = [
  { title: "VO A", period: "1 mesiac", sourcePrice: 42.5 },
  { title: "VO B", period: "1 mesiac", sourcePrice: 31 },
  { title: "VO A", period: "6 mesiacov", sourcePrice: 215 },
  { title: "VO B", period: "6 mesiacov", sourcePrice: 155 },
  { title: "VO A", period: "12 mesiacov", sourcePrice: 375 },
  { title: "VO B", period: "12 mesiacov", sourcePrice: 275 },
];

export const STREET_CATEGORIES: StreetCategory[] = [
  {
    code: "A",
    items: [
      "Alejová",
      "Bačíkova",
      "Hlinkova",
      "Hviezdoslavova",
      "Jantárová",
      "Južná trieda",
      "Komenského",
      "Kuzmányho",
      "Moldavská",
      "Moyzesova",
      "Nám. MMM",
      "Nám. osloboditeľov",
      "Nižné Kapustníky",
      "Prešovská",
      "Rastislavova",
      "Štefánikova",
      "Štúrova",
      "Toryská",
      "Tr. SNP",
      "Ul. osloboditeľov",
      "Watsonova",
    ],
  },
  {
    code: "B",
    items: [
      "Americká trieda",
      "Bottova",
      "Červený rak",
      "ČSLA",
      "Dopravná",
      "Festivalové nám.",
      "Gemerská",
      "Gorkého",
      "Herlianska",
      "Južné nábrežie",
      "Kasárenské nám.",
      "Kováčska",
      "Krmanova",
      "Ľavobrežná",
      "Letná",
      "Masarykova",
      "Moskovská",
      "Národná trieda",
      "Palackého",
      "Pod furčou",
      "Popradská",
      "Pri myslavskom potoku",
      "Pri prachárni",
      "Priemyselná",
      "Protifašistických bojovníkov",
      "Puškinova",
      "Rooseveltova",
      "Sečovská",
      "Senný trh",
      "Severné nábrežie",
      "Slanecká",
      "Staničné námestie",
      "Svätoplukova",
      "Továrenská",
      "Tr. KVP",
      "Tr. Ludvíka Svobodu",
      "Zimná",
      "Žižková",
    ],
  },
  {
    code: "C",
    items: ["Ostatné ulice", "Ulice na sídliskách", "Sadové stĺpy (do 6 m)"],
  },
];
