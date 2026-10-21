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
    | Color tokens (open-ended map), 4 radii, 4 font sizes, font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | The token map is OPEN-ENDED: add any key your design needs (e.g. a
    | `warning` pair) to both blocks and `bg-theme-warning` /
    | `text-theme-on-warning` / `border-theme-warning` resolve immediately.
    | Theme classes also accept opacity modifiers — `bg-theme-primary/15`
    | is the tonal-fill idiom (the alpha applies to the dark companion
    | too). In PHP (layout chrome builders, dynamic styling) read tokens
    | with the appearance-aware `theme()` helper: `theme('primary')`.
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

            // Secondary / muted action color.
            'secondary' => '#526571',
            'on-secondary' => '#FFFFFF',

            // Surface = cards, sheets, dialogs. Background = page root.
            'surface' => '#FFFFFF',
            'on-surface' => '#10212B',
            'background' => '#F4F7F9',
            'on-background' => '#10212B',

            // Surface variant = filled text fields, muted tonal surfaces.
            // on-surface-variant = muted label/hint text on those surfaces.
            'surface-variant' => '#EDF3F5',
            'on-surface-variant' => '#526571',

            // Text-field container, for the OUTLINED variant. Both are unset
            // on purpose, and unset means "transparent box, Material 3
            // defaults inside" — the outlined field then reads as whatever is
            // behind it, which is correct on a plain page and invisible on a
            // colored one. Declare the pair to give the field a body of its
            // own:
            //
            //   'input-fill' => '#FFFFFF',
            //   'on-input'   => '#10212B',
            //
            // `on-input` recolors everything drawn INSIDE the box — typed
            // text, placeholder, icons, prefix/suffix — so declare it
            // alongside any fill dark enough to swallow the default grays.
            // The label and supporting text sit outside the box and keep
            // taking their color from the surface behind it.

            // Outline = neutral borders (text fields, dividers, cards).
            // outline-variant = softer edges: hairline dividers, card seams.
            'input-fill' => '#FFFFFF',
            'on-input' => '#10212B',
            'outline' => '#A8B9C3',
            'outline-variant' => '#E2E8F0',

            // Destructive actions — maps to `variant="destructive"` on components.
            'destructive' => '#B91C1C',
            'on-destructive' => '#FFFFFF',

            // Success / "safe to proceed" — confirmations, verified badges.
            'success' => '#15803D',
            'on-success' => '#FFFFFF',

            // Tertiary accent — for highlights, badges, emphasis not covered by primary.
            'accent' => '#C2410C',
            'on-accent' => '#FFFFFF',
        ],

        'dark' => [
            // Leave empty or partial to auto-derive from `light` (luminance inversion).
            // Specify any token here to override the derived value.
            'primary' => '#5EEAD4',
            'on-primary' => '#073B36',

            'secondary' => '#B5C6D0',
            'on-secondary' => '#10212B',

            'surface' => '#182A35',
            'on-surface' => '#F4F7F9',
            'background' => '#10212B',
            'on-background' => '#F4F7F9',

            'surface-variant' => '#29404C',
            'on-surface-variant' => '#B5C6D0',

            'input-fill' => '#203540',
            'on-input' => '#F4F7F9',
            'outline' => '#6E8795',
            'outline-variant' => '#29404C',

            'destructive' => '#F87171',
            'on-destructive' => '#10212B',

            'success' => '#86EFAC',
            'on-success' => '#052E16',

            'accent' => '#FDBA74',
            'on-accent' => '#10212B',
        ],

        // Corner radii (points / dp).
        'radius-sm' => 4,
        'radius-md' => 12,
        'radius-lg' => 20,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,

    ],

    /*
    |---------------------------------------------------------------------------
    | Fonts
    |---------------------------------------------------------------------------
    |
    | Semantic names for bundled fonts (resources/fonts/ file tokens, minus
    | the extension). Use an alias anywhere a font token works — the `font`
    | attribute (`font="accent"`), chrome ->font() builders, or the layout
    | $font property. The `default` alias is the app-wide default font:
    | 'System' resolves to the platform face (San Francisco on iOS, Roboto
    | on Android); set a bundled token to apply it everywhere. Download one
    | with `php artisan native:font Inter --default`. Per-element `font`
    | attributes and font-serif / font-mono classes still win over the default.
    |
    |   'fonts' => [
    |       'default' => 'Inter-Regular',
    |       'accent'  => 'DynaPuff-Regular',
    |   ],
    |
    */

    'fonts' => [
        'default' => 'JannaLT-Regular',
        'heading' => 'JannaLT-Bold',
    ],

];
