<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
$landing = file_get_contents(__DIR__ . '/../views/Landing/landing_page.php');
preg_match('/<style>(.*?)<\/style>/s', $landing, $inline);
$css = "/**\n * Landing page styles (replaces Tailwind CDN)\n * Scoped to body.landing-page\n */\n\n";
$css .= "body.landing-page { font-family: 'Inter', sans-serif; scroll-behavior: smooth; background: #eff6ff; margin: 0; }\n\n";

$rules = [
    'container' => 'width:100%;max-width:1140px;margin-left:auto;margin-right:auto;',
    'mx-auto' => 'margin-left:auto;margin-right:auto;',
    'px-6' => 'padding-left:1.5rem;padding-right:1.5rem;',
    'px-4' => 'padding-left:1rem;padding-right:1rem;',
    'py-4' => 'padding-top:1rem;padding-bottom:1rem;',
    'py-3' => 'padding-top:0.75rem;padding-bottom:0.75rem;',
    'py-2' => 'padding-top:0.5rem;padding-bottom:0.5rem;',
    'py-20' => 'padding-top:5rem;padding-bottom:5rem;',
    'py-16' => 'padding-top:4rem;padding-bottom:4rem;',
    'py-12' => 'padding-top:3rem;padding-bottom:3rem;',
    'pt-24' => 'padding-top:6rem;',
    'pb-20' => 'padding-bottom:5rem;',
    'p-6' => 'padding:1.5rem;',
    'p-8' => 'padding:2rem;',
    'mb-6' => 'margin-bottom:1.5rem;',
    'mb-4' => 'margin-bottom:1rem;',
    'mb-16' => 'margin-bottom:4rem;',
    'mb-2' => 'margin-bottom:0.5rem;',
    'mt-4' => 'margin-top:1rem;',
    'mt-6' => 'margin-top:1.5rem;',
    'mt-8' => 'margin-top:2rem;',
    'mr-2' => 'margin-right:0.5rem;',
    'gap-2' => 'gap:0.5rem;',
    'gap-4' => 'gap:1rem;',
    'gap-6' => 'gap:1.5rem;',
    'gap-8' => 'gap:2rem;',
    'gap-10' => 'gap:2.5rem;',
    'gap-12' => 'gap:3rem;',
    'flex' => 'display:flex;',
    'grid' => 'display:grid;',
    'hidden' => 'display:none !important;',
    'block' => 'display:block;',
    'inline-block' => 'display:inline-block;',
    'inline-flex' => 'display:inline-flex;',
    'flex-col' => 'flex-direction:column;',
    'flex-wrap' => 'flex-wrap:wrap;',
    'flex-grow' => 'flex-grow:1;',
    'items-center' => 'align-items:center;',
    'items-start' => 'align-items:flex-start;',
    'justify-center' => 'justify-content:center;',
    'justify-between' => 'justify-content:space-between;',
    'text-center' => 'text-align:center;',
    'text-left' => 'text-align:left;',
    'text-white' => 'color:#fff;',
    'text-gray-500' => 'color:#6b7280;',
    'text-gray-600' => 'color:#4b5563;',
    'text-gray-700' => 'color:#374151;',
    'text-gray-800' => 'color:#1f2937;',
    'text-blue-600' => 'color:#2563eb;',
    'text-blue-500' => 'color:#3b82f6;',
    'bg-white' => 'background-color:#fff;',
    'bg-blue-50' => 'background-color:#eff6ff;',
    'bg-blue-500' => 'background-color:#3b82f6;',
    'bg-blue-600' => 'background-color:#2563eb;',
    'bg-gray-100' => 'background-color:#f3f4f6;',
    'bg-gradient-to-r' => 'background:linear-gradient(to right,var(--from,#dbeafe),var(--to,#e0f2fe));',
    'from-blue-100' => '--from:#dbeafe;',
    'to-sky-200' => '--to:#bae6fd;',
    'rounded-lg' => 'border-radius:0.5rem;',
    'rounded-xl' => 'border-radius:0.75rem;',
    'rounded-2xl' => 'border-radius:1rem;',
    'rounded-full' => 'border-radius:9999px;',
    'shadow-md' => 'box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1);',
    'shadow-lg' => 'box-shadow:0 10px 15px -3px rgba(0,0,0,.1),0 4px 6px -4px rgba(0,0,0,.1);',
    'shadow-xl' => 'box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1);',
    'fixed' => 'position:fixed;',
    'absolute' => 'position:absolute;',
    'relative' => 'position:relative;',
    'top-0' => 'top:0;',
    'top-full' => 'top:100%;',
    'left-0' => 'left:0;',
    'w-full' => 'width:100%;',
    'w-10' => 'width:2.5rem;',
    'h-10' => 'height:2.5rem;',
    'w-7' => 'width:1.75rem;',
    'h-7' => 'height:1.75rem;',
    'w-12' => 'width:3rem;',
    'h-12' => 'height:3rem;',
    'w-16' => 'width:4rem;',
    'h-16' => 'height:4rem;',
    'h-48' => 'height:12rem;',
    'h-64' => 'height:16rem;',
    'object-cover' => 'object-fit:cover;',
    'font-bold' => 'font-weight:700;',
    'font-semibold' => 'font-weight:600;',
    'font-extrabold' => 'font-weight:800;',
    'text-3xl' => 'font-size:1.875rem;line-height:2.25rem;',
    'text-4xl' => 'font-size:2.25rem;line-height:2.5rem;',
    'text-6xl' => 'font-size:3.75rem;line-height:1;',
    'text-xl' => 'font-size:1.25rem;line-height:1.75rem;',
    'text-lg' => 'font-size:1.125rem;line-height:1.75rem;',
    'text-sm' => 'font-size:0.875rem;line-height:1.25rem;',
    'text-xs' => 'font-size:0.75rem;line-height:1rem;',
    'leading-tight' => 'line-height:1.25;',
    'leading-relaxed' => 'line-height:1.625;',
    'z-50' => 'z-index:50;',
    'grid-cols-1' => 'grid-template-columns:repeat(1,minmax(0,1fr));',
    'grid-cols-2' => 'grid-template-columns:repeat(2,minmax(0,1fr));',
    'grid-cols-3' => 'grid-template-columns:repeat(3,minmax(0,1fr));',
    'grid-cols-4' => 'grid-template-columns:repeat(4,minmax(0,1fr));',
    'col-span-1' => 'grid-column:span 1 / span 1;',
    'max-w-2xl' => 'max-width:42rem;',
    'max-w-4xl' => 'max-width:56rem;',
    'max-w-6xl' => 'max-width:72rem;',
    'overflow-hidden' => 'overflow:hidden;',
    'underline' => 'text-decoration:underline;',
    'list-disc' => 'list-style-type:disc;',
    'list-inside' => 'list-style-position:inside;',
    'space-x-6 > * + *' => 'margin-left:1.5rem;',
    'space-y-4 > * + *' => 'margin-top:1rem;',
    'transition' => 'transition:all .3s ease;',
    'duration-300' => '',
    'transform' => 'transform:translateZ(0);',
    'scale-105' => 'transform:scale(1.05);',
    'opacity-90' => 'opacity:.9;',
    'border' => 'border:1px solid #e5e7eb;',
    'border-t' => 'border-top:1px solid #e5e7eb;',
    'border-gray-200' => 'border-color:#e5e7eb;',
    'focus:outline-none' => 'outline:none;',
    'left-1/2' => 'left:50%;',
    'translate-x-1/2' => 'transform:translateX(-50%);',
    '-translate-x-1/2' => 'transform:translateX(-50%);',
];

foreach ($rules as $cls => $decl) {
    if ($decl === '') continue;
    $sel = str_replace([':', '.', '/'], ['\\:', '\\.', '\\/'], $cls);
    if (str_contains($cls, ' > ')) {
        $css .= "body.landing-page .{$sel} { {$decl} }\n";
    } else {
        $css .= "body.landing-page .{$sel} { {$decl} }\n";
    }
}

$css .= "\nbody.landing-page .hover\\:bg-blue-600:hover { background-color:#2563eb; }\n";
$css .= "body.landing-page .hover\\:bg-blue-50:hover { background-color:#eff6ff; }\n";
$css .= "body.landing-page .hover\\:text-blue-500:hover { color:#3b82f6; }\n";
$css .= "body.landing-page .hover\\:scale-105:hover { transform:scale(1.05); }\n";
$css .= "body.landing-page .hover\\:shadow-xl:hover { box-shadow:0 20px 25px -5px rgba(0,0,0,.1); }\n";
$css .= "body.landing-page a { text-decoration:none; }\n";

$css .= "\n@media (min-width:768px) {\n";
$css .= "  body.landing-page .md\\:flex { display:flex !important; }\n";
$css .= "  body.landing-page .md\\:hidden { display:none !important; }\n";
$css .= "  body.landing-page .md\\:grid-cols-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }\n";
$css .= "  body.landing-page .md\\:grid-cols-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }\n";
$css .= "  body.landing-page .md\\:grid-cols-4 { grid-template-columns:repeat(4,minmax(0,1fr)); }\n";
$css .= "  body.landing-page .md\\:flex-row { flex-direction:row; }\n";
$css .= "  body.landing-page .md\\:w-1/2 { width:50%; }\n";
$css .= "  body.landing-page .md\\:text-left { text-align:left; }\n";
$css .= "  body.landing-page .md\\:text-4xl { font-size:2.25rem;line-height:2.5rem; }\n";
$css .= "  body.landing-page .md\\:text-6xl { font-size:3.75rem;line-height:1; }\n";
$css .= "  body.landing-page .md\\:pt-32 { padding-top:8rem; }\n";
$css .= "}\n";
$css .= "@media (min-width:1024px) {\n";
$css .= "  body.landing-page .lg\\:grid-cols-4 { grid-template-columns:repeat(4,minmax(0,1fr)); }\n";
$css .= "}\n";

if (!empty($inline[1])) {
    $css .= "\n/* Inline styles from landing_page.php */\n" . $inline[1];
}

file_put_contents(__DIR__ . '/../assets/css/landing.css', $css);
echo "Generated assets/css/landing.css (" . strlen($css) . " bytes)\n";
