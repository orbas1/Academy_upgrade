// ignore_for_file: prefer_const_constructors

import 'dart:convert';

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/email_verification_notice.dart';
import 'package:academy_lms_app/screens/forget_password.dart';
import 'package:academy_lms_app/screens/signup.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

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

  getLogin() async {
  setState(() {
    _isLoading = true;
  });

  String link = "$baseUrl/api/login";
  var navigator = Navigator.of(context);
  sharedPreferences = await SharedPreferences.getInstance();

  var map = <String, dynamic>{};
  map["email"] = _emailController.text.toString();
  map["password"] = _passwordController.text.toString();

  try {
    var response = await http.post(
      Uri.parse(link),
      body: map,
    );

    setState(() {
      _isLoading = false;
    });

    final data = jsonDecode(response.body);

    if (response.statusCode == 201) {
      final user = data["user"];
      final emailVerifiedAt = user["email_verified_at"];

      if (emailVerifiedAt != null) {
        // If email is verified, proceed with login
        setState(() {
          sharedPreferences!.setString("access_token", data["token"]);
          sharedPreferences!.setString("user", jsonEncode(user));
          sharedPreferences!.setString("email", _emailController.text.toString());
          sharedPreferences!.setString("password", _passwordController.text.toString());
        });
        token = sharedPreferences!.getString("access_token");
        navigator.pushReplacement(
          MaterialPageRoute(
            builder: (context) => const TabsScreen(
              pageIndex: 0,
            ),
          ),
        );
        Fluttertoast.showToast(msg: "Login Successful");
      } else {
        // If email is not verified, navigate to the email verification page
        Fluttertoast.showToast(
          msg: "Please verify your email before logging in.",
        );
        navigator.pushReplacement(
          MaterialPageRoute(
            builder: (context) => EmailVerificationNotice(),
          ),
        );
      }
    } else {
      Fluttertoast.showToast(msg: data['message']);
    }
  } catch (e) {
    setState(() {
      _isLoading = false;
    });
    Fluttertoast.showToast(
      msg: "An error occurred: $e",
      backgroundColor: Colors.red,
      textColor: Colors.white,
    );
  }
}


  isLogin() async {
    var navigator = Navigator.of(context);
    sharedPreferences = await SharedPreferences.getInstance();
    token = sharedPreferences!.getString("access_token");
    try {
      if (token == null) {
        // print("Token is Null");
      } else {
        Fluttertoast.showToast(msg: "Welcome Back");
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

  InputDecoration getInputDecoration(String hintext) {
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
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  @override
  Widget build(BuildContext context) {
    // _emailController.text = 'student@example.com';
    // _passwordController.text = '12345678';
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          child: Form(
            key: globalFormKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                const SizedBox(
                  height: 20,
                ),
                const Center(
                  child: Text(
                    'Log In',
                    style: TextStyle(
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
                      'E-mail',
                    ),
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    validator: (input) =>
                        !RegExp(r"[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?")
                                .hasMatch(input!)
                            ? "Email Id should be valid"
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
                    onSaved: (input) {
                      setState(() {
                        _passwordController.text = input as String;
                      });
                    },
                    validator: (input) => input!.length < 3
                        ? "Password should be more than 3 characters"
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
                      hintText: "Password",
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
                  child: _isLoading
                      ? const Center(
                          child:
                              CircularProgressIndicator(color: kDefaultColor),
                        )
                      : Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 20.0),
                          child: Center(
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
                                          msg: "Email field cannot be empty");
                                    } else if (_passwordController
                                        .text.isEmpty) {
                                      Fluttertoast.showToast(
                                          msg:
                                              "Password field cannot be empty");
                                    } else {
                                      Fluttertoast.showToast(
                                          msg:
                                              "Email & password field cannot be empty");
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
                                  child: const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        'Log In',
                                        style: TextStyle(
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
                      child: const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'Register',
                            style: TextStyle(
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
                        'Not have an account yet?',
                        style: TextStyle(
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
                          ' Sign Up',
                          style: TextStyle(
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
                      'Forget password',
                      style: TextStyle(
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
