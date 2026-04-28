export type AdTypeSlug = "billboard" | "citylight" | "bigboard" | "most" | "plachta" | "fasada" | "mega-board";

export type AdTypeContent = {
	slug: AdTypeSlug;
	title: string;
	subtitle: string;
	heroImg: string;
	accentColor: string;
	tagline: string;
	seoTitle: string;
	seoText: string[];
	specs: Array<{ label: string; value: string }>;
	advantages: Array<{ icon: string; title: string; desc: string }>;
	formats: Array<{ label: string; size: string; desc: string }>;
	useCases: string[];
	minPrice?: string;
	mapMediaType: string;
};

export const AD_TYPE_SLUGS: AdTypeSlug[] = ["billboard", "citylight", "bigboard", "most", "plachta", "fasada", "mega-board"];

export const SLUG_TO_TYPE: Record<AdTypeSlug, string> = {
	billboard: "Billboard",
	citylight: "Citylight",
	bigboard: "Bigboard",
	most: "Most",
	plachta: "Plachta",
	fasada: "Fasáda",
	"mega-board": "Mega board",
};

const commonUseCases = ["Brandové kampane", "Produktové kampane", "Retail", "Reality", "Eventy", "Sezónne akcie"];

export const TYPE_CONTENT: Record<AdTypeSlug, AdTypeContent> = {
	billboard: {
		slug: "billboard",
		title: "Billboard",
		subtitle: "Najrozšírenejší formát vonkajšej reklamy na Slovensku",
		heroImg:
			"../../assets/billboard-type.webp",
		accentColor: "#D80B17",
		tagline: "Viditeľnosť na maximum",
		seoTitle: "Billboard reklama na Slovensku - prenájom vonkajších plôch",
		seoText: [
			"Billboard je základným pilierom vonkajšej reklamy - veľkoformátová plocha umiestnená na frekventovaných cestách, križovatkách a v centrách miest.",
			"Jednoduché vizuálne spracovanie umožňuje rýchle zachytenie posolstva aj pri pohybe. Najlepšie fungujú kampane s jasným vizuálom a minimom textu.",
			"Billboardy sú vhodné pre regionálne aj celonárodné kampane pri mestských ťahoch, výjazdoch a hlavných komunikáciách.",
		],
		specs: [
			{ label: "Štandardný rozmer", value: "510 x 240 cm" },
			{ label: "Plocha", value: "12,24 m²" },
			{ label: "Konštrukcia", value: "Stĺpová, jednostranná / obojstranná" },
			{ label: "Materiál", value: "Plastová kozetka, PVC flex" },
			{ label: "Osvetlenie", value: "Voliteľné LED, 24/7" },
			{ label: "Minimálna doba", value: "14 dní" },
			{ label: "Výmena grafiky", value: "1-2 pracovné dni" },
			{ label: "Typické umiestnenie", value: "Cesty, križovatky, centrá miest" },
		],
		advantages: [
			{ icon: "lucide:eye", title: "Vysoká viditeľnosť", desc: "Veľký formát je čitateľný z diaľky aj zo strany." },
			{ icon: "lucide:target", title: "Lokálny zásah", desc: "Vyberiete konkrétnu ulicu, mestskú časť alebo ťah." },
			{ icon: "lucide:clock", title: "Non-stop expozícia", desc: "Kampaň je viditeľná počas celej doby prenájmu." },
			{ icon: "lucide:bar-chart-2", title: "Dobrý pomer ceny", desc: "Široký dosah pri rozumnej cene za kontakt." },
		],
		formats: [
			{ label: "Štandardný billboard", size: "510 x 240 cm", desc: "Mestské kampane a dostupný nákup médií." },
			{ label: "Obojstranný billboard", size: "2 plochy", desc: "Zásah v oboch smeroch premávky." },
			{ label: "Osvetlený billboard", size: "24/7", desc: "Lepšia čitateľnosť vo večerných hodinách." },
		],
		useCases: commonUseCases,
		minPrice: "79",
		mapMediaType: "billboard",
	},
	citylight: {
		slug: "citylight",
		title: "Citylight",
		subtitle: "Elegantná reklama v pešej zóne a MHD infraštruktúre",
		heroImg:
			"../../assets/clv-type.webp",
		accentColor: "#0F8B5F",
		tagline: "Blízko ľuďom, blízko nákupu",
		seoTitle: "Citylight reklama - prenájom osvetlených CLV plôch",
		seoText: [
			"Citylight je osvetlený nosič formátu 118 x 175 cm, ktorý sa nachádza na zastávkach, pri obchodných centrách, v podchodoch a peších zónach.",
			"Reklama je v úrovni očí a človek sa pri nej často pohybuje pomalšie alebo čaká. Vďaka tomu môže niesť detailnejšiu informáciu ako veľkoformátové plochy.",
			"Formát je vhodný pre retail, gastronómiu, služby, kultúru aj lokálne kampane s rýchlym spustením.",
		],
		specs: [
			{ label: "Rozmer plochy", value: "118 x 175 cm" },
			{ label: "Plocha", value: "2,065 m²" },
			{ label: "Konštrukcia", value: "Vitrína s osvetlením" },
			{ label: "Materiál", value: "Papier / podsvietený PVC flex" },
			{ label: "Umiestnenie", value: "MHD, pešie zóny, podchody" },
			{ label: "Minimálna doba", value: "14 dní" },
			{ label: "Zmena grafiky", value: "Do 48 hodín" },
			{ label: "Kontakt", value: "Peší pohyb a čakacie zóny" },
		],
		advantages: [
			{ icon: "lucide:users", title: "Blízkosť zákazníkovi", desc: "Kontakt v úrovni očí priamo na chodníku." },
			{ icon: "lucide:target", title: "Presná lokalita", desc: "Vhodné pri konkrétnych prevádzkach a zastávkach." },
			{ icon: "lucide:clock", title: "Rýchle spustenie", desc: "Dobré riešenie pre akcie a krátkodobé ponuky." },
			{ icon: "lucide:lightbulb", title: "Osvetlenie", desc: "Vizuál zostáva čitateľný aj po zotmení." },
		],
		formats: [
			{ label: "Štandardný CLV", size: "118 x 175 cm", desc: "Zastávky MHD, podchody a pešie zóny." },
			{ label: "Dvojitý CLV", size: "236 x 175 cm", desc: "Dve plochy vedľa seba pre vyššiu pozornosť." },
			{ label: "CLV Scroll", size: "118 x 175 cm", desc: "Scrollovací mechanizmus pre viac kreatív." },
		],
		useCases: ["Retail", "Gastronómia", "Móda", "Farmácia", "Kultúra", "Sezónne akcie"],
		minPrice: "79",
		mapMediaType: "citylight",
	},
	bigboard: {
		slug: "bigboard",
		title: "Bigboard",
		subtitle: "Veľkoformátová plocha pre hlavné cesty a dominantné mestské polohy",
		heroImg:
			"../../assets/bigboard-type.webp",
		accentColor: "#106EBE",
		tagline: "Väčší formát, silnejší zásah",
		seoTitle: "Bigboard reklama - prenájom veľkoformátových reklamných plôch",
		seoText: [
			"Bigboard je väčší súrodenec billboardu a používa sa tam, kde má kampaň dominovať v priestore už z veľkej vzdialenosti.",
			"Vďaka väčšej ploche je vhodný pri diaľniciach, mestských obchvatoch, hlavných ťahoch a veľkých križovatkách.",
			"Formát podporuje jednoduché brandové vizuály, veľké produktové kampane a komunikáciu, ktorá potrebuje výrazný vizuálny dopad.",
		],
		specs: [
			{ label: "Typický rozmer", value: "960 x 360 cm" },
			{ label: "Plocha", value: "34,56 m²" },
			{ label: "Konštrukcia", value: "Samostatná oceľová konštrukcia" },
			{ label: "Materiál", value: "PVC flex / veľkoformátová tlač" },
			{ label: "Osvetlenie", value: "Voliteľné" },
			{ label: "Minimálna doba", value: "1 mesiac" },
			{ label: "Viditeľnosť", value: "Diaľkové a mestské ťahy" },
			{ label: "Príprava", value: "3-5 pracovných dní" },
		],
		advantages: [
			{ icon: "lucide:ruler", title: "Veľká plocha", desc: "Výrazný formát pre rýchlu čitateľnosť z diaľky." },
			{ icon: "lucide:eye", title: "Dominantná poloha", desc: "Vizuál prirodzene vystúpi z rušného prostredia." },
			{ icon: "lucide:target", title: "Silné ťahy", desc: "Vhodné pri výjazdoch, obchvatoch a hlavných trasách." },
			{ icon: "lucide:bar-chart-2", title: "Brandový efekt", desc: "Dobré riešenie pre kampane s vysokým dosahom." },
		],
		formats: [
			{ label: "Štandardný bigboard", size: "960 x 360 cm", desc: "Hlavné cesty a mestské ťahy." },
			{ label: "Osvetlený bigboard", size: "24/7", desc: "Lepší výkon pri večernej premávke." },
			{ label: "Prémiový bigboard", size: "Top poloha", desc: "Najfrekventovanejšie dopravné body." },
		],
		useCases: commonUseCases,
		minPrice: "599",
		mapMediaType: "bigboard",
	},
	most: {
		slug: "most",
		title: "Most",
		subtitle: "Reklamná plocha na mostných konštrukciách a dopravných uzloch",
		heroImg:
			"../../assets/most.webp",
		accentColor: "#334155",
		tagline: "Viditeľné v prúde dopravy",
		seoTitle: "Reklama na mostoch - prenájom plôch pri dopravných uzloch",
		seoText: [
			"Mostné reklamné plochy využívajú prirodzené dopravné body, kde sa pozornosť vodičov a chodcov sústreďuje na jeden koridor.",
			"Formát je vhodný pre kampane pri mestských vstupoch, nadjazdoch, podjazdoch a frekventovaných uzloch.",
			"Vďaka stabilnej polohe a opakovanému prejazdu dokáže mostná reklama budovať silnú lokálnu známosť.",
		],
		specs: [
			{ label: "Rozmer", value: "Individuálny podľa konštrukcie" },
			{ label: "Materiál", value: "PVC flex / banner / panel" },
			{ label: "Umiestnenie", value: "Mosty, nadjazdy, podjazdy" },
			{ label: "Viditeľnosť", value: "Dopravné koridory" },
			{ label: "Minimálna doba", value: "1 mesiac" },
			{ label: "Inštalácia", value: "Podľa technických možností" },
			{ label: "Osvetlenie", value: "Podľa lokality" },
			{ label: "Príprava", value: "Individuálna kalkulácia" },
		],
		advantages: [
			{ icon: "lucide:map-pin", title: "Silný dopravný bod", desc: "Plocha je priamo v trase každodenného pohybu." },
			{ icon: "lucide:eye", title: "Výborný uhol pohľadu", desc: "Reklama sa zobrazuje v prirodzenej línii jazdy." },
			{ icon: "lucide:clock", title: "Opakovaný kontakt", desc: "Vhodné pre lokality s pravidelnou dochádzkou." },
			{ icon: "lucide:target", title: "Lokálny zásah", desc: "Dobré riešenie pre mestské a regionálne kampane." },
		],
		formats: [
			{ label: "Mostný banner", size: "Individuálny", desc: "Flex alebo plachta na mostnej konštrukcii." },
			{ label: "Panel pri nadjazde", size: "Podľa lokality", desc: "Statická plocha pri dopravnom uzle." },
			{ label: "Prémiový most", size: "Top uzol", desc: "Najfrekventovanejšie mestské koridory." },
		],
		useCases: commonUseCases,
		// minPrice: "350",
		mapMediaType: "bridge",
	},
	plachta: {
		slug: "plachta",
		title: "Plachta",
		subtitle: "Flexibilný bannerový formát pre fasády, ploty a dočasné plochy",
		heroImg:
			"../../assets/plachta.webp",
		accentColor: "#B45309",
		tagline: "Rýchla plocha pre výrazné posolstvo",
		seoTitle: "Reklamná plachta - prenájom a umiestnenie bannerových plôch",
		seoText: [
			"Plachta je flexibilný reklamný nosič, ktorý sa dá prispôsobiť existujúcej konštrukcii, plotu, fasáde alebo dočasnému priestoru.",
			"Je vhodná tam, kde je potrebné rýchle nasadenie, väčší rozmer a dobrá cenová dostupnosť.",
			"Formát sa využíva pri eventoch, developerských projektoch, sezónnych ponukách a lokálnych kampaniach.",
		],
		specs: [
			{ label: "Rozmer", value: "Individuálny" },
			{ label: "Materiál", value: "PVC banner / mesh" },
			{ label: "Kotvenie", value: "Očká, lanká, rám alebo konštrukcia" },
			{ label: "Tlač", value: "UV odolná veľkoformátová tlač" },
			{ label: "Minimálna doba", value: "14 dní" },
			{ label: "Použitie", value: "Ploty, fasády, eventové plochy" },
			{ label: "Príprava", value: "2-5 pracovných dní" },
			{ label: "Odolnosť", value: "Exteriér, vietor podľa kotvenia" },
		],
		advantages: [
			{ icon: "lucide:ruler", title: "Variabilný rozmer", desc: "Plochu je možné prispôsobiť dostupnému priestoru." },
			{ icon: "lucide:clock", title: "Rýchla realizácia", desc: "Dobrá voľba pre krátke aj sezónne kampane." },
			{ icon: "lucide:target", title: "Lokálne použitie", desc: "Funguje pri prevádzkach, stavbách a eventoch." },
			{ icon: "lucide:bar-chart-2", title: "Dostupná cena", desc: "Výhodný pomer veľkosti a nákladov." },
		],
		formats: [
			{ label: "PVC plachta", size: "Individuálny", desc: "Univerzálny banner pre exteriér." },
			{ label: "Mesh plachta", size: "Individuálny", desc: "Priepustný materiál pre väčšie plochy." },
			{ label: "Eventová plachta", size: "Krátkodobo", desc: "Rýchle kampane a podujatia." },
		],
		useCases: ["Eventy", "Stavebné projekty", "Retail", "Otvorenie prevádzky", "Sezónne akcie", "Lokálne služby"],
		// minPrice: "120",
		mapMediaType: "banner",
	},
	fasada: {
		slug: "fasada",
		title: "Fasáda",
		subtitle: "Veľkoformátová reklama na budovách a viditeľných stenách",
		heroImg:
			"../../assets/fasada-type.webp",
		accentColor: "#151719",
		tagline: "Budova ako reklamný nosič",
		seoTitle: "Fasádna reklama - prenájom veľkoformátových plôch na budovách",
		seoText: [
			"Fasádna reklama využíva plochy budov na inštaláciu bannerov, mesh plachiet alebo panelových riešení.",
			"Formát je vhodný pre dlhšie kampane, prémiové značky a komunikáciu, ktorá má dominovať v mestskom priestore.",
			"Najčastejšie sa používa na frekventovaných uliciach, námestiach, pri rekonštrukciách a na viditeľných bočných stenách budov.",
		],
		specs: [
			{ label: "Rozmer", value: "10-500 m²" },
			{ label: "Materiál", value: "Mesh, PVC, Backlit" },
			{ label: "Tlač", value: "Solventná, UV odolná" },
			{ label: "Inštalácia", value: "Lešenie / výškové práce" },
			{ label: "Odolnosť", value: "UV, vietor podľa kotvenia" },
			{ label: "Minimálna doba", value: "1 mesiac" },
			{ label: "Príprava", value: "5-10 pracovných dní" },
			{ label: "Viditeľnosť", value: "Podľa polohy budovy" },
		],
		advantages: [
			{ icon: "lucide:eye", title: "Dominantný formát", desc: "Budova sa mení na výraznú reklamnú plochu." },
			{ icon: "lucide:ruler", title: "Veľký rozmer", desc: "Plocha je limitovaná najmä veľkosťou fasády." },
			{ icon: "lucide:clock", title: "Dlhá životnosť", desc: "Materiál zvládne dlhšie kampane v exteriéri." },
			{ icon: "lucide:target", title: "Prémiové polohy", desc: "Centrá miest, námestia a frekventované ulice." },
		],
		formats: [
			{ label: "Fasádny banner", size: "10-50 m²", desc: "Menšie budovy a bočné steny." },
			{ label: "Lešeniový kryt", size: "50-200 m²", desc: "Rekonštrukcie budov a dlhšie kampane." },
			{ label: "Veľká fasáda", size: "200 m²+", desc: "Dominantná plocha v centre mesta." },
		],
		useCases: ["Prémiové značky", "Automotive", "Reality", "Rekonštrukcie", "Móda", "Filmové premiéry"],
		// minPrice: "800",
		mapMediaType: "facade",
	},
	"mega-board": {
		slug: "mega-board",
		title: "Mega board",
		subtitle: "Najväčšie outdoor formáty pre kampane s maximálnym dopadom",
		heroImg:
			"../../assets/megaboard.webp",
		accentColor: "#7F1D1D",
		tagline: "Maximálny rozmer, maximálna pozornosť",
		seoTitle: "Mega board - prenájom najväčších reklamných plôch",
		seoText: [
			"Mega board je určený pre kampane, ktoré potrebujú dominovať v priestore a pracovať s veľkým vizuálnym gestom.",
			"Používa sa na výnimočných mestských a dopravných polohách, kde štandardný formát nestačí.",
			"Je vhodný pre uvedenie produktov, image kampane, veľké eventy a značky, ktoré potrebujú prémiovú viditeľnosť.",
		],
		specs: [
			{ label: "Rozmer", value: "Od 20 x 8 m vyššie" },
			{ label: "Materiál", value: "PVC, mesh, panelové riešenie" },
			{ label: "Umiestnenie", value: "Prémiové mestské a cestné polohy" },
			{ label: "Inštalácia", value: "Individuálne podľa konštrukcie" },
			{ label: "Osvetlenie", value: "Podľa lokality" },
			{ label: "Minimálna doba", value: "1 mesiac" },
			{ label: "Príprava", value: "Individuálny harmonogram" },
			{ label: "Cieľ", value: "Maximálny vizuálny dopad" },
		],
		advantages: [
			{ icon: "lucide:ruler", title: "Extrémny rozmer", desc: "Formát určený pre kampane, ktoré majú byť neprehliadnuteľné." },
			{ icon: "lucide:eye", title: "Prémiová viditeľnosť", desc: "Výborné pre mestské dominanty a veľké dopravné uzly." },
			{ icon: "lucide:target", title: "Silný brand recall", desc: "Veľký vizuál pomáha rýchlemu zapamätaniu značky." },
			{ icon: "lucide:bar-chart-2", title: "Image efekt", desc: "Riešenie pre kampane s vysokou hodnotou zásahu." },
		],
		formats: [
			{ label: "Megaboard", size: "od 20 x 8 m", desc: "Veľký formát pri silných dopravných bodoch." },
			{ label: "Mestská dominanta", size: "Individuálny", desc: "Plocha na výnimočnej mestskej polohe." },
			{ label: "Špeciálna konštrukcia", size: "Na mieru", desc: "Riešenie podľa konkrétnej lokality." },
		],
		useCases: ["Image kampane", "Produktové launche", "Automotive", "Retail siete", "Eventy", "Prémiové značky"],
		// minPrice: "1200",
		mapMediaType: "mega",
	},
};
