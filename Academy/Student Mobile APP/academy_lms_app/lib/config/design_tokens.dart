import 'package:flutter/material.dart';

class DesignColors {
  static const Color primary50 = Color(0xFFEEF2FF);
  static const Color primary100 = Color(0xFFE0E7FF);
  static const Color primary200 = Color(0xFFC7D2FE);
  static const Color primary300 = Color(0xFFA5B4FC);
  static const Color primary400 = Color(0xFF818CF8);
  static const Color primary500 = Color(0xFF1C64F2);
  static const Color primary600 = Color(0xFF1A56DB);
  static const Color primary700 = Color(0xFF1E3A8A);

  static const Color neutral50 = Color(0xFFF8FAFC);
  static const Color neutral100 = Color(0xFFF1F5F9);
  static const Color neutral200 = Color(0xFFE2E8F0);
  static const Color neutral300 = Color(0xFFCBD5E1);
  static const Color neutral400 = Color(0xFF94A3B8);
  static const Color neutral500 = Color(0xFF64748B);
  static const Color neutral600 = Color(0xFF475569);
  static const Color neutral900 = Color(0xFF0F172A);

  static const Color success500 = Color(0xFF10B981);
  static const Color warning500 = Color(0xFFF59E0B);
  static const Color danger500 = Color(0xFFEF4444);
  static const Color paywall500 = Color(0xFFE5A663);

  static const Color surface = neutral50;
  static const Color surfaceElevated = Colors.white;
  static const Color borderSubtle = neutral200;
  static const Color borderStrong = neutral300;
  static const Color textPrimary = neutral900;
  static const Color textSecondary = neutral600;
  static const Color textMuted = neutral500;
}

class DesignSpacing {
  static const double xxs = 4;
  static const double xs = 8;
  static const double sm = 12;
  static const double md = 16;
  static const double lg = 20;
  static const double xl = 24;
  static const double xxl = 32;
}

class DesignRadii {
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 24;
  static const double pill = 999;

  static const BorderRadius card = BorderRadius.all(Radius.circular(xl));
  static const BorderRadius pillRadius = BorderRadius.all(Radius.circular(pill));
}

class DesignShadows {
  static final List<BoxShadow> card = [
    BoxShadow(
      color: Colors.black.withOpacity(0.08),
      blurRadius: 24,
      offset: const Offset(0, 14),
      spreadRadius: -6,
    ),
  ];
}

class DesignTypography {
  static const String fontFamilySans = 'Poppins';

  static TextTheme apply(TextTheme base) {
    return base.copyWith(
      titleLarge: base.titleLarge?.copyWith(
        fontWeight: FontWeight.w600,
        color: DesignColors.textPrimary,
      ),
      titleMedium: base.titleMedium?.copyWith(
        fontWeight: FontWeight.w600,
        color: DesignColors.textPrimary,
      ),
      titleSmall: base.titleSmall?.copyWith(
        fontWeight: FontWeight.w600,
        color: DesignColors.textPrimary,
      ),
      bodyLarge: base.bodyLarge?.copyWith(color: DesignColors.textSecondary),
      bodyMedium: base.bodyMedium?.copyWith(color: DesignColors.textSecondary),
      bodySmall: base.bodySmall?.copyWith(color: DesignColors.textMuted),
      labelLarge: base.labelLarge?.copyWith(
        fontWeight: FontWeight.w600,
        letterSpacing: 0.2,
      ),
      labelSmall: base.labelSmall?.copyWith(
        fontWeight: FontWeight.w600,
        letterSpacing: 0.5,
      ),
    ).apply(
      bodyColor: DesignColors.textPrimary,
      displayColor: DesignColors.textPrimary,
      fontFamily: fontFamilySans,
    );
  }
}

ThemeData buildAppTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: const ColorScheme(
      brightness: Brightness.light,
      primary: DesignColors.primary600,
      onPrimary: Colors.white,
      secondary: DesignColors.paywall500,
      onSecondary: Colors.white,
      surface: DesignColors.surface,
      onSurface: DesignColors.textPrimary,
      background: DesignColors.surface,
      onBackground: DesignColors.textPrimary,
      error: DesignColors.danger500,
      onError: Colors.white,
      tertiary: DesignColors.success500,
      onTertiary: Colors.white,
      surfaceVariant: DesignColors.neutral100,
      onSurfaceVariant: DesignColors.textSecondary,
      outline: DesignColors.borderStrong,
    ),
    scaffoldBackgroundColor: DesignColors.surface,
    fontFamily: DesignTypography.fontFamilySans,
  );

  final textTheme = DesignTypography.apply(base.textTheme);

  return base.copyWith(
    textTheme: textTheme,
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: DesignColors.primary600,
        foregroundColor: Colors.white,
        textStyle: textTheme.labelLarge,
        padding: const EdgeInsets.symmetric(
          horizontal: DesignSpacing.lg,
          vertical: DesignSpacing.sm,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(DesignRadii.lg),
        ),
        elevation: 2,
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: DesignColors.primary600,
        side: const BorderSide(color: DesignColors.primary300, width: 1.4),
        textStyle: textTheme.labelLarge,
        padding: const EdgeInsets.symmetric(
          horizontal: DesignSpacing.lg,
          vertical: DesignSpacing.sm,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(DesignRadii.lg),
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: DesignColors.primary600,
        textStyle: textTheme.labelLarge,
        padding: const EdgeInsets.symmetric(horizontal: DesignSpacing.sm),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(
        horizontal: DesignSpacing.lg,
        vertical: DesignSpacing.sm,
      ),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(DesignRadii.lg),
        borderSide: const BorderSide(color: DesignColors.borderSubtle),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(DesignRadii.lg),
        borderSide: const BorderSide(color: DesignColors.borderSubtle),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(DesignRadii.lg),
        borderSide: const BorderSide(color: DesignColors.primary500, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(DesignRadii.lg),
        borderSide: const BorderSide(color: DesignColors.danger500, width: 1.5),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(DesignRadii.lg),
        borderSide: const BorderSide(color: DesignColors.danger500, width: 2),
      ),
      hintStyle: textTheme.bodyMedium?.copyWith(color: DesignColors.textMuted),
      labelStyle: textTheme.labelLarge?.copyWith(color: DesignColors.textSecondary),
    ),
    cardTheme: CardTheme(
      margin: EdgeInsets.zero,
      color: DesignColors.surfaceElevated,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(DesignRadii.xl),
      ),
      elevation: 2,
      shadowColor: Colors.black.withOpacity(0.08),
    ),
    chipTheme: base.chipTheme.copyWith(
      backgroundColor: DesignColors.neutral100,
      selectedColor: DesignColors.primary100,
      labelStyle: textTheme.labelSmall,
      padding: const EdgeInsets.symmetric(
        horizontal: DesignSpacing.sm,
        vertical: DesignSpacing.xs,
      ),
      shape: const StadiumBorder(),
    ),
    dividerColor: DesignColors.borderSubtle,
    appBarTheme: base.appBarTheme.copyWith(
      backgroundColor: DesignColors.surfaceElevated,
      foregroundColor: DesignColors.textPrimary,
      elevation: 0,
      titleTextStyle: textTheme.titleLarge,
    ),
    snackBarTheme: base.snackBarTheme.copyWith(
      backgroundColor: DesignColors.neutral900,
      contentTextStyle: textTheme.bodyMedium?.copyWith(color: Colors.white),
    ),
  );
}
