<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Native\Mobile\UI\Theme::merge([...])
 * from a service provider. Runtime merges deep-merge on top of these values.
 *
 * Decision log: /docs/NATIVE-UI-REWRITE-PLAN.md (D — theme layer)
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Theme
    |---------------------------------------------------------------------------
    |
    | 17 color tokens, 4 radii, 4 font sizes, font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | Color tokens accept:
    |   - CSS hex: '#B91C1C', '#F00', or with alpha '#8B5CF680' (#RRGGBBAA)
    |   - Tailwind palette names: 'red-300', 'orange-800'
    |   - Opacity modifiers on either: 'red-300/20', '#8B5CF6/50'
    |
    | Dark mode is auto-derived from `light` when `dark` is not set. To opt
    | into explicit dark tokens, fill out the `dark` block.
    |
    | The default pairs meet WCAG AA (4.5:1) — if you customize, keep each
    | `on-*` color at 4.5:1 contrast against its background token.
    |
    */

    'theme' => [

        'light' => [
            // Primary brand color — used for filled buttons, active states, key accents.
            'primary' => '#0F766E',
            'on-primary' => '#FFFFFF',

            // Secondary = tonal teal for supporting actions next to a primary one.
            'secondary' => '#CCE8E4',
            'on-secondary' => '#134E4A',

            // Tinted containers: home hero card, AI message bubbles, safety card.
            'primary-container' => '#E6F4F2',
            'on-primary-container' => '#134E4A',
            'warning-container' => '#FEF3C7',
            'on-warning-container' => '#78350F',

            // Surface = cards, sheets, dialogs. Background = page root.
            'surface' => '#FFFFFF',
            'on-surface' => '#1B1B18',
            'background' => '#F6F7F6',
            'on-background' => '#1B1B18',

            // Surface variant = filled text fields, muted tonal surfaces.
            // on-surface-variant = muted label/hint text on those surfaces.
            'surface-variant' => '#EEF1F0',
            'on-surface-variant' => '#5F625F',

            // Outline = neutral borders (text fields, dividers, cards).
            'outline' => '#E5E5E5',

            // Destructive actions — maps to `variant="destructive"` on components.
            'destructive' => '#B91C1C',
            'on-destructive' => '#FFFFFF',

            // Tertiary accent — for highlights, badges, emphasis not covered by primary.
            'accent' => '#C2410C',
            'on-accent' => '#FFFFFF',
        ],

        'dark' => [
            // Leave empty or partial to auto-derive from `light` (luminance inversion).
            // Specify any token here to override the derived value.
            'primary' => '#14B8A6',
            'on-primary' => '#FFFFFF',

            'secondary' => '#1E3B38',
            'on-secondary' => '#99F6E4',

            'primary-container' => '#12302D',
            'on-primary-container' => '#CCFBF1',
            'warning-container' => '#3A2A0A',
            'on-warning-container' => '#FCD34D',

            'surface' => '#161615',
            'on-surface' => '#EDEDEC',
            'background' => '#0A0A0A',
            'on-background' => '#EDEDEC',

            'surface-variant' => '#1F1F1E',
            'on-surface-variant' => '#A1A09A',

            'outline' => '#3E3E3A',

            'destructive' => '#F87171',
            'on-destructive' => '#0F172A',

            'accent' => '#FDBA74',
            'on-accent' => '#0F172A',
        ],

        // Corner radii (points / dp).
        'radius-sm' => 4,
        'radius-md' => 8,
        'radius-lg' => 16,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,
    ],

    'fonts' => [
        'default' => 'System',
        'accent' => 'Archivo+Black-Regular',
        'lobster' => 'Lobster+Two-Regular',
    ],

];
