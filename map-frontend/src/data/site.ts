export const SITE_NAME = "Billboardy.sk";
export const SITE_ORIGIN = (import.meta.env.PUBLIC_SITE_URL || "https://www.billboardy.sk").replace(/\/+$/, "");
export const DEFAULT_TITLE = "Billboardy.sk - Vaša cesta k úspešnej vonkajšej reklame";
export const DEFAULT_DESCRIPTION =
	"Prenájom billboardov, bigboardov, citylightov a reklamných plôch po celom Slovensku. Zabezpečíme tlač, inštaláciu aj výber plochy v interaktívnej mape.";
export const DEFAULT_OG_IMAGE = "/og-image.png";
export const CONTACT_PHONE_DISPLAY = "+421 917 930 494";
export const CONTACT_PHONE_TEL = "+421917930494";
export const CONTACT_EMAIL = "obchod@kpkreklama.sk";
export const GTM_ID = import.meta.env.PUBLIC_GTM_ID?.trim() ?? "";

export function withBasePath(path = "") {
	const basePath = import.meta.env.BASE_URL || "/";
	const normalizedBase = basePath.endsWith("/") ? basePath : `${basePath}/`;
	const cleanPath = path.replace(/^\/+/, "");

	if (!cleanPath) {
		return normalizedBase;
	}

	return `${normalizedBase}${cleanPath}`;
}

export function absoluteUrl(pathOrUrl = "") {
	if (/^https?:\/\//i.test(pathOrUrl)) {
		return pathOrUrl;
	}

	return new URL(withBasePath(pathOrUrl), SITE_ORIGIN).toString();
}
