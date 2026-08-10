import { AD_TYPE_SLUGS, TYPE_CONTENT } from "../data/adTypes";
import { absoluteUrl } from "../data/site";

export const prerender = true;

const staticRoutes = [
	{ path: "/", changefreq: "weekly", priority: "1.0" },
	{ path: "/reklama-na-mhd-kosice/", changefreq: "monthly", priority: "0.8" },
	{ path: "/kontakt/", changefreq: "monthly", priority: "0.7" },
	{ path: "/gdprcookies/", changefreq: "yearly", priority: "0.2" },
];

const typeRoutes = AD_TYPE_SLUGS.map((slug) => ({
	path: `/typ/${slug}/`,
	changefreq: "weekly",
	priority: TYPE_CONTENT[slug].slug === "billboard" ? "0.9" : "0.8",
}));

const routes = [...staticRoutes, ...typeRoutes];

function escapeXml(value: string) {
	return value
		.replace(/&/g, "&amp;")
		.replace(/</g, "&lt;")
		.replace(/>/g, "&gt;")
		.replace(/"/g, "&quot;")
		.replace(/'/g, "&apos;");
}

export function GET() {
	const urls = routes
		.map(
			(route) => `  <url>
    <loc>${escapeXml(absoluteUrl(route.path))}</loc>
    <changefreq>${route.changefreq}</changefreq>
    <priority>${route.priority}</priority>
  </url>`,
		)
		.join("\n");

	const body = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>
`;

	return new Response(body, {
		headers: {
			"Content-Type": "application/xml; charset=utf-8",
		},
	});
}
