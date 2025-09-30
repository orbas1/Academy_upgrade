// ignore_for_file: prefer_const_constructors

import 'dart:convert';

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/l10n/app_localizations.dart';
import 'package:academy_lms_app/screens/email_verification_notice.dart';
import 'package:academy_lms_app/screens/forget_password.dart';
import 'package:academy_lms_app/screens/signup.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:academy_lms_app/services/security/auth_session.dart';
import 'package:academy_lms_app/services/security/auth_session_manager.dart';
import 'package:academy_lms_app/services/security/device_identity_provider.dart';
import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';

import '../providers/auth.dart';
class LoginScreen extends StatefulWidget {
  static const routeName = '/login';
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  GlobalKey<FormState> globalFormKey = GlobalKey<FormState>();
  final scaffoldKey = GlobalKey<ScaffoldState>();

  bool hidePassword = true;
  bool _isLoading = false;
  bool _rememberDevice = true;
  String? token;

  SharedPreferences? sharedPreferences;
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();


  // getLogin() async {
  //   setState(() {
  //     _isLoading = true;
  //   });

  // String link = "$baseUrl/api/login";
  //   var navigator = Navigator.of(context);
  //   sharedPreferences = await SharedPreferences.getInstance();
  //   var map = <String, dynamic>{};
  //   map["email"] = _emailController.text.toString();
  //   map["password"] = _passwordController.text.toString();
  //   var response = await http.post(
  //     Uri.parse(link),
  //     body: map,
  //   );

  //   setState(() {
  //     _isLoading = false;
  //   });

  //   final data = jsonDecode(response.body);
  //   // print(data['message']);
  //   if (response.statusCode == 201) {
  //     setState(() {
  //       sharedPreferences!.setString("access_token", data["token"]);
  //       sharedPreferences!.setString("user", jsonEncode(data["user"]));
  //       sharedPreferences!.setString("email", _emailController.text.toString());
  //       sharedPreferences!
  //           .setString("password", _passwordController.text.toString());
  //     });
  //     token = sharedPreferences!.getString("access_token");
  //     // print("Token Saved $token");
  //     navigator.pushReplacement(MaterialPageRoute(
  //         builder: (context) => const TabsScreen(
  //               pageIndex: 0,
  //             )));
  //     Fluttertoast.showToast(msg: "Login Successful");
  //   } else {
  //     Fluttertoast.showToast(msg: data['message']);
  //   }
  // }

  Future<void> getLogin() async {
    final localizations = AppLocalizations.of(context);
    setState(() {
      _isLoading = true;
    });

    sharedPreferences = await SharedPreferences.getInstance();

    try {
      final identity = await DeviceIdentityProvider.instance.getIdentity();

      final response = await http.post(
        Uri.parse('$baseUrl/api/login'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...identity.toHeaders(),
        },
        body: jsonEncode({
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
          'device_token': identity.deviceId,
          'remember_device': _rememberDevice,
        }),
      );

      final Map<String, dynamic> data =
          jsonDecode(response.body) as Map<String, dynamic>;

      if (response.statusCode == 202 && data['two_factor_required'] == true) {
        final success = await _handleTwoFactorChallenge(data, identity);
        if (!success) {
          setState(() {
            _isLoading = false;
          });
        }
        return;
      }

      setState(() {
        _isLoading = false;
      });

      if (response.statusCode == 201) {
        await _finalizeLogin(data);
      } else {
        Fluttertoast.showToast(
          msg: data['message']?.toString() ?? localizations.genericError,
        );
      }
    } catch (error) {
      setState(() {
        _isLoading = false;
      });
      Fluttertoast.showToast(
        msg: '${localizations.genericError} $error',
        backgroundColor: Colors.red,
        textColor: Colors.white,
      );
    }
  }

  Future<bool> _handleTwoFactorChallenge(
    Map<String, dynamic> payload,
    DeviceIdentity identity,
  ) async {
    final localizations = AppLocalizations.of(context);
    final challengeToken = payload['challenge_token'] as String?;

    if (challengeToken == null || challengeToken.isEmpty) {
      Fluttertoast.showToast(msg: localizations.genericError);
      return false;
    }

    final result = await _promptTwoFactorCode(challengeToken, identity);

    if (result == null) {
      return false;
    }

    if (!mounted) {
      return false;
    }

    setState(() {
      _isLoading = false;
    });

    await _finalizeLogin(result);
    return true;
  }

  Future<Map<String, dynamic>?> _promptTwoFactorCode(
    String challengeToken,
    DeviceIdentity identity,
  ) {
    final localizations = AppLocalizations.of(context);
    final codeController = TextEditingController();

    return showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        bool remember = _rememberDevice;
        bool submitting = false;
        String? errorMessage;

        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: SafeArea(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        localizations.twoFactorTitle,
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 8),
                      Text(
                        localizations.twoFactorDescription,
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: codeController,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          labelText: localizations.twoFactorCodeHint,
                          errorText: errorMessage,
                        ),
                        autofocus: true,
                      ),
                      SwitchListTile.adaptive(
                        value: remember,
                        onChanged: (value) {
                          setModalState(() {
                            remember = value;
                          });
                        },
                        title: Text(localizations.twoFactorRememberDevice),
                        contentPadding: EdgeInsets.zero,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: ElevatedButton(
                              onPressed: submitting
                                  ? null
                                  : () async {
                                      setModalState(() {
                                        submitting = true;
                                        errorMessage = null;
                                      });

                                      final response = await http.post(
                                        Uri.parse('$baseUrl/api/two-factor/verify'),
                                        headers: {
                                          'Content-Type': 'application/json',
                                          'Accept': 'application/json',
                                          ...identity.toHeaders(),
                                        },
                                        body: jsonEncode({
                                          'challenge_token': challengeToken,
                                          'code': codeController.text.trim(),
                                          'remember_device': remember,
                                        }),
                                      );

                                      if (response.statusCode == 201) {
                                        _rememberDevice = remember;
                                        Navigator.of(context).pop(
                                          jsonDecode(response.body)
                                              as Map<String, dynamic>,
                                        );
                                      } else {
                                        final Map<String, dynamic> body =
                                            jsonDecode(response.body)
                                                as Map<String, dynamic>;
                                        setModalState(() {
                                          submitting = false;
                                          errorMessage = body['message']
                                                  ?.toString() ??
                                              localizations.genericError;
                                        });
                                      }
                                    },
                              child: submitting
                                  ? const SizedBox(
                                      height: 18,
                                      width: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : Text(localizations.twoFactorSubmit),
                            ),
                          ),
                          const SizedBox(width: 12),
                          TextButton(
                            onPressed: submitting
                                ? null
                                : () => Navigator.of(context).pop(),
                            child: Text(localizations.twoFactorCancel),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _finalizeLogin(Map<String, dynamic> data) async {
    final localizations = AppLocalizations.of(context);
    final navigator = Navigator.of(context);
    final user = data['user'] as Map<String, dynamic>?;

    if (user == null) {
      throw const FormatException('User payload missing from authentication response.');
    }

    final emailVerifiedAt = user['email_verified_at'];

    if (emailVerifiedAt == null) {
      Fluttertoast.showToast(msg: localizations.emailVerificationRequired);
      if (!mounted) {
        return;
      }
      navigator.pushReplacement(
        MaterialPageRoute(
          builder: (context) => EmailVerificationNotice(),
        ),
      );
      return;
    }

    final session = AuthSession.fromOAuthJson(data);
    await AuthSessionManager.instance.persistSession(session);

    sharedPreferences ??= await SharedPreferences.getInstance();

    sharedPreferences!.setString('user', jsonEncode(user));
    sharedPreferences!.setString('email', _emailController.text.trim());
    sharedPreferences!.setString('password', _passwordController.text);

    token = await AuthSessionManager.instance.getValidAccessToken();
    await Provider.of<Auth>(context, listen: false).synchronizeToken();

    if (!mounted) {
      return;
    }

    navigator.pushReplacement(
      MaterialPageRoute(
        builder: (context) => const TabsScreen(
          pageIndex: 0,
        ),
      ),
    );

    Fluttertoast.showToast(msg: localizations.loginSuccess);
  }


  isLogin() async {
    var navigator = Navigator.of(context);
    sharedPreferences = await SharedPreferences.getInstance();
    token = await AuthSessionManager.instance.getValidAccessToken();
    try {
      if (token == null) {
        // print("Token is Null");
      } else {
        Fluttertoast.showToast(msg: AppLocalizations.of(context).welcomeBack);
        navigator.pushReplacement(MaterialPageRoute(
            builder: (context) => const TabsScreen(
                  pageIndex: 0,
                )));
      }
    } catch (e) {
      // print("Exception is $e");
    }
  }

  @override
  void initState() {
    isLogin();
    super.initState();
  }

  InputDecoration getInputDecoration(String hintText, {String? labelText}) {
    return InputDecoration(
      enabledBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      border: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintText,
      labelText: labelText ?? hintText,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    // _emailController.text = 'student@example.com';
    // _passwordController.text = '12345678';
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          child: Form(
            key: globalFormKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                const SizedBox(
                  height: 20,
                ),
                Center(
                  child: Text(
                    localizations.loginTitle,
                    style: const TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                const SizedBox(
                  height: 20,
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 20.0, vertical: 5.0),
                  child: TextFormField(
                    style: const TextStyle(fontSize: 14),
                    decoration: getInputDecoration(
                      localizations.emailLabel,
                      labelText: localizations.emailLabel,
                    ),
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next,
                    autofillHints: const [AutofillHints.email],
                    validator: (input) =>
                        !RegExp(r"[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?")
                                .hasMatch(input!)
                            ? localizations.emailValidationError
                            : null,
                    onSaved: (value) {
                      setState(() {
                        _emailController.text = value as String;
                      });
                    },
                  ),
                ),
                const SizedBox(
                  height: 10,
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 20.0, vertical: 5.0),
                  child: TextFormField(
                    style: const TextStyle(color: Colors.black),
                    keyboardType: TextInputType.text,
                    controller: _passwordController,
                    textInputAction: TextInputAction.done,
                    autofillHints: const [AutofillHints.password],
                    onSaved: (input) {
                      setState(() {
                        _passwordController.text = input as String;
                      });
                    },
                    validator: (input) => input!.length < 3
                        ? localizations.passwordValidationError
                        : null,
                    obscureText: hidePassword,
                    decoration: InputDecoration(
                      enabledBorder: OutlineInputBorder(
                        borderRadius:
                            const BorderRadius.all(Radius.circular(16.0)),
                        borderSide: BorderSide(
                            color: kDefaultColor.withOpacity(0.1), width: 1),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius:
                            const BorderRadius.all(Radius.circular(16.0)),
                        borderSide: BorderSide(
                            color: kDefaultColor.withOpacity(0.1), width: 1),
                      ),
                      border: OutlineInputBorder(
                        borderRadius:
                            const BorderRadius.all(Radius.circular(16.0)),
                        borderSide: BorderSide(
                            color: kDefaultColor.withOpacity(0.1), width: 1),
                      ),
                      filled: true,
                      hintStyle:
                          const TextStyle(color: Colors.black54, fontSize: 14),
                      hintText: localizations.passwordLabel,
                      labelText: localizations.passwordLabel,
                      fillColor: kInputBoxBackGroundColor,
                      contentPadding: const EdgeInsets.symmetric(
                          vertical: 18, horizontal: 15),
                      suffixIcon: IconButton(
                        onPressed: () {
                          setState(() {
                            hidePassword = !hidePassword;
                          });
                        },
                        color: kInputBoxIconColor,
                        icon: Icon(hidePassword
                            ? Icons.visibility_off_outlined
                            : Icons.visibility_outlined),
                      ),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(top: 20.0),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20.0),
                    child: SwitchListTile.adaptive(
                      value: _rememberDevice,
                      contentPadding: EdgeInsets.zero,
                      title: Text(localizations.rememberDeviceLabel),
                      onChanged: (value) {
                        setState(() {
                          _rememberDevice = value;
                        });
                      },
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.only(top: 10.0),
                  child: _isLoading
                      ? const Center(
                          child:
                              CircularProgressIndicator(color: kDefaultColor),
                        )
                      : Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 20.0),
                          child: Center(
                            child: Semantics(
                              button: true,
                              label: localizations.loginButton,
                              child: Stack(
                                children: [
                                  Positioned.fill(
                                    child: Container(
                                      decoration: BoxDecoration(
                                        borderRadius: BorderRadius.circular(16),
                                        gradient: const LinearGradient(
                                          colors: [
                                            Color(0xFFCC61FF),
                                            Color(0xFF5851EF),
                                          ],
                                          stops: [0.05, 0.88],
                                          begin: Alignment.topLeft,
                                          end: Alignment.centerLeft,
                                        ),
                                      ),
                                    ),
                                  ),
                                  MaterialButton(
                                    elevation: 0,
                                    onPressed: () {
                                      if (_emailController.text.isNotEmpty &&
                                          _passwordController.text.isNotEmpty) {
                                        getLogin();
                                      } else if (_emailController.text.isEmpty) {
                                        Fluttertoast.showToast(
                                            msg: localizations.emptyEmailError);
                                      } else if (_passwordController
                                          .text.isEmpty) {
                                        Fluttertoast.showToast(
                                            msg:
                                                localizations.emptyPasswordError);
                                      } else {
                                        Fluttertoast.showToast(
                                            msg: localizations
                                                .emptyCredentialsError);
                                      }
                                    },
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 20, vertical: 16),
                                    shape: RoundedRectangleBorder(
                                      borderRadius:
                                          BorderRadiusDirectional.circular(16),
                                      side: BorderSide(
                                        color: kGreyLightColor.withOpacity(0.3),
                                        width: 1.0,
                                      ),
                                    ),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.center,
                                      children: [
                                        Text(
                                          localizations.loginButton,
                                          style: const TextStyle(
                                            fontSize: 16,
                                            color: kWhiteColor,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                ),
                SizedBox(
                  width: double.infinity,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 20.0, vertical: 15),
                    child: MaterialButton(
                      elevation: 0,
                      color: Colors.white,
                      onPressed: () {
                        Navigator.of(context).push(MaterialPageRoute(
                            builder: (context) => const SignUpScreen()));
                      },
                      padding: const EdgeInsets.symmetric(
                          horizontal: 20, vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadiusDirectional.circular(16),
                        side: BorderSide(
                          color: kGreyLightColor.withOpacity(0.3),
                          width: 1.0,
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            localizations.registerButton,
                            style: const TextStyle(
                              fontSize: 16,
                              color: kInputBoxIconColor,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(
                  height: 60,
                ),
                const SizedBox(
                  height: 60,
                ),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        localizations.accountCta,
                        style: const TextStyle(
                          color: kGreyLightColor,
                          fontSize: 16,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      GestureDetector(
                        onTap: () {
                          Navigator.of(context).push(MaterialPageRoute(
                              builder: (context) => const SignUpScreen()));
                        },
                        child: Text(
                          ' ${localizations.signUp}',
                          style: const TextStyle(
                            color: kSignUpTextColor,
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(
                  height: 60,
                ),
                Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20.0),
                  child: TextButton(
                    onPressed: () {
                      Navigator.of(context).push(MaterialPageRoute(
                          builder: (context) => const ForgetPasswordScreen()));
                    },
                    child: Text(
                      localizations.forgotPassword,
                      style: const TextStyle(
                        color: kGreyLightColor,
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ),
                const SizedBox(
                  height: 25,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
