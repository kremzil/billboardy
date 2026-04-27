import { absoluteUrl, withBasePath } from "../data/site";

export const prerender = true;

export function GET() {
	const basePath = withBasePath("/");
	const body = [
		"User-agent: *",
		`Allow: ${basePath}`,
		"",
		`Sitemap: ${absoluteUrl("sitemap.xml")}`,
		"",
	].join("\n");

	return new Response(body, {
		headers: {
			"Content-Type": "text/plain; charset=utf-8",
		},
	});
}
