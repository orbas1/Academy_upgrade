import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

class AppLocalizations {
  AppLocalizations(this.locale);

  final Locale locale;

  static const supportedLocales = <Locale>[
    Locale('en'),
    Locale('es'),
    Locale('ar'),
  ];

  static const _localizedStrings = <String, Map<String, String>>{
    'en': {
      'appTitle': 'Orbas Learn',
      'loginTitle': 'Log In',
      'emailLabel': 'E-mail',
      'emailValidationError': 'Please enter a valid email address.',
      'passwordLabel': 'Password',
      'passwordValidationError': 'Password should be at least 3 characters.',
      'loginButton': 'Log In',
      'registerButton': 'Register',
      'loginSuccess': 'Login Successful',
      'emailVerificationRequired': 'Please verify your email before logging in.',
      'emptyEmailError': 'Email field cannot be empty',
      'emptyPasswordError': 'Password field cannot be empty',
      'emptyCredentialsError': 'Email & password field cannot be empty',
      'accountCta': "Don't have an account yet?",
      'signUp': 'Sign Up',
      'forgotPassword': 'Forgot password',
      'welcomeBack': 'Welcome Back',
      'genericError': 'An unexpected error occurred.',
      'splashImageLabel': 'Illustration showing students learning online',
      'rememberDeviceLabel': 'Trust this device after sign-in',
      'twoFactorTitle': 'Two-factor authentication',
      'twoFactorDescription':
          'Enter the verification code from your authenticator app to continue.',
      'twoFactorCodeHint': 'Authentication code',
      'twoFactorRememberDevice': 'Remember this device',
      'twoFactorSubmit': 'Verify and continue',
      'twoFactorCancel': 'Cancel',
      'deviceSecurityTitle': 'Device & Session Security',
      'deviceSecuritySubtitle':
          'Review active sessions and revoke access from devices you do not recognize.',
      'deviceSecurityCurrentDevice': 'Current device',
      'deviceSecurityTrusted': 'Trusted',
      'deviceSecurityUntrusted': 'Not trusted',
      'deviceSecurityRevoked': 'Revoked',
      'deviceSecurityRevoke': 'Revoke access',
      'deviceSecurityRevokeConfirmTitle': 'Revoke device access?',
      'deviceSecurityRevokeConfirmMessage':
          'The selected device will be signed out immediately. This action cannot be undone.',
      'deviceSecurityNoDevices': 'No device sessions recorded yet.',
      'deviceSecurityLastSeen': 'Last seen',
      'deviceSecurityIpAddress': 'IP',
      'deviceSecurityTrustToggle': 'Mark as trusted',
      'deviceSecurityRefresh': 'Pull to refresh',
    },
    'es': {
      'appTitle': 'Aplicación Orbas Learn',
      'loginTitle': 'Iniciar sesión',
      'emailLabel': 'Correo electrónico',
      'emailValidationError': 'Introduce una dirección de correo válida.',
      'passwordLabel': 'Contraseña',
      'passwordValidationError': 'La contraseña debe tener al menos 3 caracteres.',
      'loginButton': 'Iniciar sesión',
      'registerButton': 'Registrarse',
      'loginSuccess': 'Inicio de sesión correcto',
      'emailVerificationRequired': 'Verifica tu correo antes de iniciar sesión.',
      'emptyEmailError': 'El correo no puede estar vacío',
      'emptyPasswordError': 'La contraseña no puede estar vacía',
      'emptyCredentialsError': 'El correo y la contraseña no pueden estar vacíos',
      'accountCta': '¿Aún no tienes una cuenta?',
      'signUp': 'Regístrate',
      'forgotPassword': 'Olvidé mi contraseña',
      'welcomeBack': 'Bienvenido de nuevo',
      'genericError': 'Ocurrió un error inesperado.',
      'splashImageLabel': 'Ilustración de estudiantes aprendiendo en línea',
      'rememberDeviceLabel': 'Confiar en este dispositivo tras iniciar sesión',
      'twoFactorTitle': 'Autenticación en dos pasos',
      'twoFactorDescription':
          'Introduce el código de verificación de tu aplicación de autenticación.',
      'twoFactorCodeHint': 'Código de verificación',
      'twoFactorRememberDevice': 'Recordar este dispositivo',
      'twoFactorSubmit': 'Verificar y continuar',
      'twoFactorCancel': 'Cancelar',
      'deviceSecurityTitle': 'Seguridad de dispositivos y sesiones',
      'deviceSecuritySubtitle':
          'Revisa los dispositivos con sesión iniciada y revoca los que no reconozcas.',
      'deviceSecurityCurrentDevice': 'Dispositivo actual',
      'deviceSecurityTrusted': 'Confiable',
      'deviceSecurityUntrusted': 'No confiable',
      'deviceSecurityRevoked': 'Revocado',
      'deviceSecurityRevoke': 'Revocar acceso',
      'deviceSecurityRevokeConfirmTitle': '¿Revocar acceso del dispositivo?',
      'deviceSecurityRevokeConfirmMessage':
          'El dispositivo seleccionado cerrará la sesión de inmediato. Esta acción no se puede deshacer.',
      'deviceSecurityNoDevices': 'Aún no hay sesiones de dispositivo registradas.',
      'deviceSecurityLastSeen': 'Última actividad',
      'deviceSecurityIpAddress': 'IP',
      'deviceSecurityTrustToggle': 'Marcar como confiable',
      'deviceSecurityRefresh': 'Desliza para actualizar',
    },
    'ar': {
      'appTitle': 'تطبيق أورباس ليرن',
      'loginTitle': 'تسجيل الدخول',
      'emailLabel': 'البريد الإلكتروني',
      'emailValidationError': 'يرجى إدخال بريد إلكتروني صالح.',
      'passwordLabel': 'كلمة المرور',
      'passwordValidationError': 'يجب أن تتكون كلمة المرور من 3 أحرف على الأقل.',
      'loginButton': 'تسجيل الدخول',
      'registerButton': 'إنشاء حساب',
      'loginSuccess': 'تم تسجيل الدخول بنجاح',
      'emailVerificationRequired': 'يرجى تفعيل بريدك الإلكتروني قبل تسجيل الدخول.',
      'emptyEmailError': 'لا يمكن ترك البريد الإلكتروني فارغًا',
      'emptyPasswordError': 'لا يمكن ترك كلمة المرور فارغة',
      'emptyCredentialsError': 'لا يمكن ترك البريد وكلمة المرور فارغين',
      'accountCta': 'لا تملك حسابًا بعد؟',
      'signUp': 'سجّل الآن',
      'forgotPassword': 'نسيت كلمة المرور',
      'welcomeBack': 'مرحبًا بعودتك',
      'genericError': 'حدث خطأ غير متوقع.',
      'splashImageLabel': 'رسم توضيحي لطلاب يتعلمون عبر الإنترنت',
      'rememberDeviceLabel': 'الثقة بهذا الجهاز بعد تسجيل الدخول',
      'twoFactorTitle': 'المصادقة الثنائية',
      'twoFactorDescription':
          'أدخل الرمز المكون من 6 أرقام من تطبيق المصادقة للمتابعة.',
      'twoFactorCodeHint': 'رمز المصادقة',
      'twoFactorRememberDevice': 'تذكر هذا الجهاز',
      'twoFactorSubmit': 'تحقق واستمر',
      'twoFactorCancel': 'إلغاء',
      'deviceSecurityTitle': 'أمان الأجهزة والجلسات',
      'deviceSecuritySubtitle':
          'راجع الأجهزة التي سجلت الدخول إلى حسابك وقم بإلغاء غير المألوف منها.',
      'deviceSecurityCurrentDevice': 'الجهاز الحالي',
      'deviceSecurityTrusted': 'موثوق',
      'deviceSecurityUntrusted': 'غير موثوق',
      'deviceSecurityRevoked': 'تم الإلغاء',
      'deviceSecurityRevoke': 'إلغاء الوصول',
      'deviceSecurityRevokeConfirmTitle': 'إلغاء وصول الجهاز؟',
      'deviceSecurityRevokeConfirmMessage':
          'سيتم تسجيل خروج الجهاز المحدد فوراً. لا يمكن التراجع عن ذلك.',
      'deviceSecurityNoDevices': 'لا توجد جلسات أجهزة مسجلة بعد.',
      'deviceSecurityLastSeen': 'آخر ظهور',
      'deviceSecurityIpAddress': 'عنوان IP',
      'deviceSecurityTrustToggle': 'وضع علامة موثوق',
      'deviceSecurityRefresh': 'اسحب للتحديث',
    },
  };

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static bool isSupported(Locale locale) {
    return _localizedStrings.keys.contains(locale.languageCode);
  }

  static Locale resolutionCallback(Locale? locale, Iterable<Locale> supportedLocales) {
    if (locale == null) {
      return supportedLocales.first;
    }

    return supportedLocales.firstWhere(
      (supported) => supported.languageCode == locale.languageCode,
      orElse: () => supportedLocales.first,
    );
  }

  String _translate(String key) {
    final languageCode = locale.languageCode;
    final translations = _localizedStrings[languageCode] ?? _localizedStrings['en']!;
    return translations[key] ?? _localizedStrings['en']![key] ?? key;
  }

  String get appTitle => _translate('appTitle');
  String get loginTitle => _translate('loginTitle');
  String get emailLabel => _translate('emailLabel');
  String get emailValidationError => _translate('emailValidationError');
  String get passwordLabel => _translate('passwordLabel');
  String get passwordValidationError => _translate('passwordValidationError');
  String get loginButton => _translate('loginButton');
  String get registerButton => _translate('registerButton');
  String get loginSuccess => _translate('loginSuccess');
  String get emailVerificationRequired => _translate('emailVerificationRequired');
  String get emptyEmailError => _translate('emptyEmailError');
  String get emptyPasswordError => _translate('emptyPasswordError');
  String get emptyCredentialsError => _translate('emptyCredentialsError');
  String get accountCta => _translate('accountCta');
  String get signUp => _translate('signUp');
  String get forgotPassword => _translate('forgotPassword');
  String get welcomeBack => _translate('welcomeBack');
  String get genericError => _translate('genericError');
  String get splashImageLabel => _translate('splashImageLabel');
  String get rememberDeviceLabel => _translate('rememberDeviceLabel');
  String get twoFactorTitle => _translate('twoFactorTitle');
  String get twoFactorDescription => _translate('twoFactorDescription');
  String get twoFactorCodeHint => _translate('twoFactorCodeHint');
  String get twoFactorRememberDevice => _translate('twoFactorRememberDevice');
  String get twoFactorSubmit => _translate('twoFactorSubmit');
  String get twoFactorCancel => _translate('twoFactorCancel');
  String get deviceSecurityTitle => _translate('deviceSecurityTitle');
  String get deviceSecuritySubtitle => _translate('deviceSecuritySubtitle');
  String get deviceSecurityCurrentDevice =>
      _translate('deviceSecurityCurrentDevice');
  String get deviceSecurityTrusted => _translate('deviceSecurityTrusted');
  String get deviceSecurityUntrusted => _translate('deviceSecurityUntrusted');
  String get deviceSecurityRevoked => _translate('deviceSecurityRevoked');
  String get deviceSecurityRevoke => _translate('deviceSecurityRevoke');
  String get deviceSecurityRevokeConfirmTitle =>
      _translate('deviceSecurityRevokeConfirmTitle');
  String get deviceSecurityRevokeConfirmMessage =>
      _translate('deviceSecurityRevokeConfirmMessage');
  String get deviceSecurityNoDevices => _translate('deviceSecurityNoDevices');
  String get deviceSecurityLastSeen => _translate('deviceSecurityLastSeen');
  String get deviceSecurityIpAddress =>
      _translate('deviceSecurityIpAddress');
  String get deviceSecurityTrustToggle =>
      _translate('deviceSecurityTrustToggle');
  String get deviceSecurityRefresh => _translate('deviceSecurityRefresh');
}

class AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) => AppLocalizations.isSupported(locale);

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(AppLocalizations(locale));
  }

  @override
  bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) => false;
}
