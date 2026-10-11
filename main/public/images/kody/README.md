# Kody replaceable art set, version 1

Original project artwork generated on 2026-10-11 with the built-in imagegen tool.
No third-party game/brand assets were imported. These are design placeholders;
the owner may replace them via `config/branding.php`. Keep the original SVG mark
available as an alternate. Source PNGs retain original dimensions/alpha; WebP
derivatives preserve proportions/alpha, use quality 86 and are used by the site.
Delivery: three mascot poses 384 px wide, KodeBit 256 px, ascent 724 px.
SVG XP/rank/Run/warning/satellite drawings are original editable code-native art.
Animation lives in `resources/css/kody-world.css` and the companion/scenery JS.

## Exact built-in generation prompts

### head-source.png → head.webp and favicons (mascot reference edit)

Use case: precise-object-edit. Asset type: Kody website logo and favicon. Input image: edit target, the existing blue axolotl Kody mascot. Create a standalone head-only brand mark from this same character, preserving the blue/cyan face, friendly large navy eyes, smiling mouth and three rounded blue gill fronds on each side. Remove the entire body, backpack, hands, tail and star. Straight-on symmetrical close-up, centered within a square, broad readable silhouette filling about 90 percent of the canvas with a little transparent edge padding. Simplify tiny spots and highlights, retain a strong clean navy outline and crisp cel-shaded cartoon colors so the face remains recognizable at 16 and 32 pixels. True transparent background, no text, no frame, no extra objects. This is an adaptation of the same character, not a new mascot.

Generated with the built-in imagegen tool using `mascot-source.png` as reference.
`head-source.png` retains the transparent original. PHP GD creates `head.webp`
(256 × 256, quality 90), transparent PNG favicon sizes 16/32, and the 180 × 180
Apple icon on a pale blue background. Root `public/favicon.ico` contains PNG
images at 16/32/48 pixels. `config/branding.php` owns all delivery paths.
Full-body mascot poses remain unchanged. Cabinet illustrations and additional
Tower scenery symbols are original editable SVG in Blade/navigation.svg.

### celebration-source.png → celebration.webp (mascot reference edit)

Edit the referenced Kody mascot into a celebration pose as a standalone transparent website game-result asset. Preserve exactly the same original cute blue axolotl identity, round proportions, blue spotted gill fronds, large dark eyes, cyan belly, mint backpack straps and yellow star badge, clean cel-shaded cartoon and navy outlines. Change only pose/expression: cheerful eyes and smiling mouth, both small hands raised in celebration, one small mint and yellow star sparkle either side. One full-body character only, centered with generous transparent padding, no text, no watermark, no background. Do not add other characters, props or badges. Actual transparency.

### thinking-source.png → thinking.webp (mascot reference edit)

Edit the referenced Kody mascot into a gentle thinking pose as a standalone transparent website error/help asset. Preserve exactly the same original cute blue axolotl identity, round proportions, blue spotted gill fronds, large dark eyes, cyan belly, mint backpack straps and yellow star badge, clean cel-shaded cartoon and navy outlines. Change only pose/expression: one little hand touching the cheek, head slightly tilted, thoughtful curious eyes with one eyebrow raised and a small reassuring smile. Never distressed or crying. One full-body character only, centered with generous transparent padding, no text, no watermark, no background, no extra symbols. Actual transparency.

### mascot-source.png → mascot.webp

Use case: stylized-concept. Asset type: reusable website mascot/logo cutout for Kody, a friendly gamified programming learning platform. Create one original charming cartoon blue axolotl coding explorer, front facing, full body, short round proportions, wide expressive dark eyes, tiny smiling mouth, three rounded blue external gill fronds on each side of its head, soft cyan belly, small mint backpack straps and tiny star badge, waving one hand. Clean polished flat cartoon illustration with gentle cel shading and crisp dark navy outlines, blue/grey primary palette with mint accent, recognizable at 32px as well as large. Entire character centered with generous transparent padding, true transparent background. No text, no letters, no watermark, no UI, no duplicate characters. Suitable original replaceable brand mascot, warm playful professional educational website.

### ascent-source.png → ascent.webp

Use case: illustration-story. Asset type: vertically scrolling cartoon scenic backdrop for an original coding-learning Tower game. A tall portrait panoramic world viewed straight on, stylized polished flat cartoon with soft cel-shaded shapes, clear clean navy outlines and blue mint lavender palette. Bottom fifth: green rolling land, a whimsical blue coding tower among round bushes; above that tall lush trees and distant mountains; middle: blue sky and fluffy clouds; upper: atmospheric indigo space with a small satellite and pale moon; very top: sparse asteroids and colorful ringed planets. Landscape at outer sides, center third very open and low detail for numbered level buttons over it. Continuous vertical ascent transitions, no panels/no border, no text, no logos, no watermarks, no character, no UI buttons, peaceful professional playful educational game backdrop. Composition very tall portrait approximately 1:3.

### kodebit-source.png → kodebit.webp

Use case: stylized-concept. Asset type: KodeBit virtual currency UI icon and purchase-plan illustration for Kody coding learning platform. One original chunky rounded hexagonal blue coin, three-quarter front angle, cyan face with a bold dark navy embossed coding bracket symbol </> centered, beveled royal blue rim, small mint glow accents and one tiny yellow sparkle. Polished friendly cel-shaded cartoon, clean strong outline, simple recognizable silhouette at 32 pixels. Entire single coin centered with generous transparent padding, actual transparent background. No text or numerals apart from the code bracket glyph, no watermark, no duplicated coins, no background, no UI. Consistent with friendly blue cartoon axolotl mascot.
