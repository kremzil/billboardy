import { AD_TYPE_SLUGS, TYPE_CONTENT } from "../data/adTypes";
import { absoluteUrl, DEFAULT_DESCRIPTION, SITE_NAME } from "../data/site";

export const prerender = true;

const apiBase = "/wp-json/billboardy/v1";

export function GET() {
	const typeLinks = AD_TYPE_SLUGS
		.map((slug) => {
			const content = TYPE_CONTENT[slug];

			return `- [${content.title}](${absoluteUrl(`/typ/${slug}/`)}): ${content.subtitle}`;
		})
		.join("\n");

	const body = `# ${SITE_NAME}

> ${DEFAULT_DESCRIPTION}

${SITE_NAME} is a Slovak outdoor advertising website for finding and requesting billboard, bigboard, citylight, bridge, banner, facade, and mega-board advertising spaces across Slovakia.

## Important Pages

- [Homepage and interactive map](${absoluteUrl("/")})
- [Contact](${absoluteUrl("/kontakt/")})
- [GDPR and cookies](${absoluteUrl("/gdprcookies/")})

## Advertising Formats

${typeLinks}

## Public Map API

The frontend uses these public read endpoints from the WordPress API:

- \`GET ${apiBase}/map-points\`: lightweight map points, bounds filtering, and clustering payloads.
- \`GET ${apiBase}/ad-spaces\`: paginated advertising-space listings and search results.
- \`GET ${apiBase}/filters\`: available filter metadata.

Do not assume that administrative import, WordPress admin, or private WooCommerce endpoints are public integration surfaces.

## Agent Notes

- The primary user task is choosing outdoor advertising space by location and media type, then sending a non-binding inquiry.
- The map is interactive and loads current availability from the public API at runtime.
- Public content usage preferences are declared in \`robots.txt\`: \`ai-train=no, search=yes, ai-input=yes\`.
- Visible user-facing copy on the site is Slovak.
`;

	return new Response(body, {
		headers: {
			"Content-Type": "text/markdown; charset=utf-8",
		},
	});
}
