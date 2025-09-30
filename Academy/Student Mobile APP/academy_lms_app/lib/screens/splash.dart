// ignore_for_file: use_build_context_synchronously

import 'package:academy_lms_app/l10n/app_localizations.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    donLogin();
    super.initState();
  }

  void donLogin() {
    Future.delayed(const Duration(seconds: 3), () async {
      Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const TabsScreen()));
    });
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    return Scaffold(
      body: Center(
        child: SizedBox(
          height: MediaQuery.of(context).size.height,
          width: double.infinity,
          child: Image.asset(
            'assets/images/splash.png',
            fit: BoxFit.cover,
            semanticLabel: localizations.splashImageLabel,
            excludeFromSemantics: false,
          ),
        ),
      ),
    );
  }
}
