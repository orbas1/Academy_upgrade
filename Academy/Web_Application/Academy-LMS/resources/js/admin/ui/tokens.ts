export const palette = {
    primary: {
        50: '#eef2ff',
        100: '#e0e7ff',
        200: '#c7d2fe',
        300: '#a5b4fc',
        400: '#818cf8',
        500: '#6366f1',
        600: '#4f46e5',
        700: '#4338ca',
        800: '#3730a3',
        900: '#312e81',
    },
    emerald: '#10b981',
    slate: {
        50: '#f8fafc',
        100: '#f1f5f9',
        200: '#e2e8f0',
        300: '#cbd5f5',
        400: '#94a3b8',
        500: '#64748b',
        600: '#475569',
        700: '#334155',
        800: '#1e293b',
        900: '#0f172a',
    },
    danger: '#ef4444',
    warning: '#f59e0b',
    success: '#16a34a',
};

export const typography = {
    fontFamilySans: "'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
    fontSizes: {
        xs: '0.75rem',
        sm: '0.875rem',
        base: '1rem',
        lg: '1.125rem',
        xl: '1.25rem',
    },
    lineHeights: {
        relaxed: 1.7,
        snug: 1.35,
    },
};

export const radii = {
    xs: '6px',
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '24px',
};

export const shadows = {
    card: '0 10px 30px -12px rgba(15, 23, 42, 0.35)',
    subtle: '0 4px 12px -6px rgba(15, 23, 42, 0.4)',
};

export const transitions = {
    default: 'all 180ms ease-in-out',
};

export function focusRing(color: string = palette.primary[500]): string {
    return `0 0 0 3px ${color}33`;
}
