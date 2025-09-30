const palette = {
    primary: {
        50: '#EEF2FF',
        100: '#E0E7FF',
        200: '#C7D2FE',
        300: '#A5B4FC',
        400: '#818CF8',
        500: '#1C64F2',
        600: '#1A56DB',
        700: '#1E3A8A',
    },
    slate: {
        50: '#F8FAFC',
        100: '#F1F5F9',
        200: '#E2E8F0',
        300: '#CBD5E1',
        400: '#94A3B8',
        500: '#64748B',
        600: '#475569',
        700: '#334155',
        900: '#0F172A',
    },
    emerald: {
        500: '#10B981',
    },
    danger: {
        500: '#EF4444',
    },
    warning: {
        500: '#F59E0B',
    },
    paywall: {
        500: '#E5A663',
    },
} as const;

const semanticColors = {
    surface: {
        page: palette.slate[50],
        card: '#FFFFFF',
        muted: palette.slate[100],
    },
    text: {
        primary: palette.slate[900],
        secondary: palette.slate[600],
        muted: palette.slate[500],
        inverse: '#FFFFFF',
    },
    border: {
        subtle: palette.slate[200],
        strong: palette.slate[300],
    },
    status: {
        success: palette.emerald[500],
        warning: palette.warning[500],
        danger: palette.danger[500],
        paywall: palette.paywall[500],
    },
} as const;

const typography = {
    fontFamilySans:
        "'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    fontWeights: {
        regular: 400,
        medium: 500,
        semibold: 600,
        bold: 700,
    },
    fontSizes: {
        xs: '0.75rem',
        sm: '0.875rem',
        base: '1rem',
        lg: '1.125rem',
        xl: '1.25rem',
    },
    lineHeights: {
        tight: 1.2,
        snug: 1.35,
        relaxed: 1.7,
    },
    letterSpacing: {
        tight: '-0.01em',
        normal: '0',
        wide: '0.08em',
    },
} as const;

const spacing = {
    xxs: '0.25rem',
    xs: '0.5rem',
    sm: '0.75rem',
    md: '1rem',
    lg: '1.25rem',
    xl: '1.5rem',
    xxl: '2rem',
} as const;

const radii = {
    xs: '6px',
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '24px',
    pill: '999px',
} as const;

const shadows = {
    xs: '0 1px 2px rgba(15, 23, 42, 0.08)',
    sm: '0 4px 12px rgba(15, 23, 42, 0.08)',
    md: '0 10px 30px -12px rgba(15, 23, 42, 0.25)',
    lg: '0 18px 40px -12px rgba(15, 23, 42, 0.35)',
} as const;

const transitions = {
    default: 'all 180ms ease-in-out',
    slow: 'all 320ms cubic-bezier(0.16, 1, 0.3, 1)',
} as const;

const zIndices = {
    base: 1,
    sticky: 10,
    dropdown: 1000,
    modal: 1100,
    toast: 1200,
} as const;

const borderWidths = {
    hairline: '1px',
    thin: '1.5px',
    thick: '2px',
} as const;

export interface DesignTokens {
    palette: typeof palette;
    semantic: typeof semanticColors;
    typography: typeof typography;
    spacing: typeof spacing;
    radii: typeof radii;
    shadows: typeof shadows;
    transitions: typeof transitions;
    zIndices: typeof zIndices;
    borderWidths: typeof borderWidths;
}

export const designTokens: DesignTokens = {
    palette,
    semantic: semanticColors,
    typography,
    spacing,
    radii,
    shadows,
    transitions,
    zIndices,
    borderWidths,
};

function toKebabCase(value: string): string {
    return value
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .replace(/[_\s]+/g, '-')
        .toLowerCase();
}

function flattenTokenTree(prefix: string, value: unknown, accumulator: Record<string, string>): void {
    if (value === undefined || value === null) {
        return;
    }

    if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
        const key = prefix.startsWith('--ds-') ? prefix : `--ds-${prefix}`;
        accumulator[key] = String(value);
        return;
    }

    if (Array.isArray(value)) {
        value.forEach((entry, index) => {
            const nextPrefix = prefix ? `${prefix}-${index}` : String(index);
            flattenTokenTree(nextPrefix, entry, accumulator);
        });
        return;
    }

    if (typeof value === 'object') {
        Object.entries(value as Record<string, unknown>).forEach(([childKey, childValue]) => {
            const nextPrefix = prefix ? `${prefix}-${toKebabCase(childKey)}` : toKebabCase(childKey);
            flattenTokenTree(nextPrefix, childValue, accumulator);
        });
    }
}

export function tokensToCssVariables(overrides?: Partial<DesignTokens>): Record<string, string> {
    const variables: Record<string, string> = {};
    flattenTokenTree('', designTokens, variables);

    if (overrides) {
        flattenTokenTree('', overrides as Record<string, unknown>, variables);
    }

    return variables;
}

export function applyDesignTokens({
    tokens,
    target,
}: {
    tokens?: Partial<DesignTokens>;
    target?: HTMLElement | null;
} = {}): void {
    if (typeof window === 'undefined') {
        return;
    }

    const element = target ?? document.documentElement;
    if (!element) {
        return;
    }

    const variables = tokensToCssVariables(tokens);
    Object.entries(variables).forEach(([key, value]) => {
        element.style.setProperty(key, value);
    });
}

export function focusRing(color: string = designTokens.palette.primary[500]): string {
    return `0 0 0 3px ${color}33`;
}

export { palette, typography, spacing, radii, shadows, transitions, zIndices, borderWidths, semanticColors };
