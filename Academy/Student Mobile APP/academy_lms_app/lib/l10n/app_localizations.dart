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
      'appTitle': 'Academy LMS App',
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
    },
    'es': {
      'appTitle': 'Aplicación Academy LMS',
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
    },
    'ar': {
      'appTitle': 'تطبيق أكاديمي إل إم إس',
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
