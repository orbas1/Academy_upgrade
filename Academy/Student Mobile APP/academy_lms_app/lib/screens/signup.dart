// ignore_for_file: prefer_const_constructors, non_constant_identifier_names, avoid_print, prefer_final_fields

import 'dart:convert';

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/email_verification_notice.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class SignUpScreen extends StatefulWidget {
  // static const routeName = '/signup';
  const SignUpScreen({super.key});

  @override
  State<SignUpScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<SignUpScreen> {
  GlobalKey<FormState> globalFormKey = GlobalKey<FormState>();
  final scaffoldKey = GlobalKey<ScaffoldState>();

  bool hidePassword = true;
  bool hideConPassword = true;
  bool _isLoading = false;
  String? token;

  SharedPreferences? sharedPreferences;
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _conPasswordController = TextEditingController();

  // Future<void> signup(
  //   String name,
  //   String email,
  //   String password,
  //   String password_confirmation,
  // ) async {
  //   sharedPreferences = await SharedPreferences.getInstance();
  //   // dynamic tokens = sharedPreferences!.getString("access_token");

  //   var urls = "$baseUrl/api/signup?type=registration";
  //   try {
  //     final responses = await http.post(
  //       Uri.parse(urls),
  //       headers: {
  //         'Content-Type': 'application/json',
  //         'Accept': 'application/json',
  //       },
  //       body: json.encode({
  //         'name': name,
  //         'email': email,
  //         'password': password,
  //         'password_confirmation': password_confirmation,
  //       }),
  //     );

  //     if (responses.statusCode == 200) {
  //       final responseData = jsonDecode(responses.body);

  //       if (responseData['success']) {
  //         Fluttertoast.showToast(
  //           msg: "User created successfully",
  //           toastLength: Toast.LENGTH_SHORT,
  //           gravity: ToastGravity.BOTTOM,
  //           timeInSecForIosWeb: 2,
  //           backgroundColor: Colors.grey,
  //           textColor: Colors.white,
  //           fontSize: 16.0,
  //         );
  //       } else {
  //         // Handle other responses if needed
  //       }
  //     } else if (responses.statusCode == 422) {
  //       final responseData = jsonDecode(responses.body);

  //       if (responseData['validationError'] != null) {
  //         responseData['validationError'].forEach((key, value) {
  //           Fluttertoast.showToast(
  //             msg: value[0], // Display the first error message
  //             toastLength: Toast.LENGTH_SHORT,
  //             gravity: ToastGravity.BOTTOM,
  //             timeInSecForIosWeb: 2,
  //             backgroundColor: Colors.red,
  //             textColor: Colors.white,
  //             fontSize: 16.0,
  //           );
  //         });
  //       }
  //     } else {
  //       Fluttertoast.showToast(
  //         msg: "An error occurred",
  //         toastLength: Toast.LENGTH_SHORT,
  //         gravity: ToastGravity.BOTTOM,
  //         timeInSecForIosWeb: 2,
  //         backgroundColor: Colors.red,
  //         textColor: Colors.white,
  //         fontSize: 16.0,
  //       );
  //     }
  //   } catch (error) {
  //     // rethrow;
  //     print('Error: $error');
  //   }
  // }

  Future<void> signup(
    String name,
    String email,
    String password,
    String password_confirmation,
    BuildContext context, // Added context parameter
  ) async {
    sharedPreferences = await SharedPreferences.getInstance();

    var urls = "$baseUrl/api/signup?type=registration";
    setState(() {
        _isLoading = true;
      });
    try {
      final responses = await http.post(
        Uri.parse(urls),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': password_confirmation,
        }),
      );

      if (responses.statusCode == 201) {
        final responseData = jsonDecode(responses.body);

        if (responseData['success'] == true) {
          // Success condition
          if (responseData['student_email_verification'] == "1") {
            // Email verification is required
            Fluttertoast.showToast(
              msg: "Email sent to the user for verification.",
              toastLength: Toast.LENGTH_SHORT,
              gravity: ToastGravity.BOTTOM,
              timeInSecForIosWeb: 2,
              backgroundColor: Colors.grey,
              textColor: Colors.white,
              fontSize: 16.0,
            );
           setState(() {
        _isLoading = false; // Stop loading
      });

            // Navigate to the email verification page
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(
                builder: (context) => EmailVerificationNotice(),
              ),
            );
          } else {
            // No email verification required
            Fluttertoast.showToast(
              msg: "User created successfully",
              toastLength: Toast.LENGTH_SHORT,
              gravity: ToastGravity.BOTTOM,
              timeInSecForIosWeb: 2,
              backgroundColor: Colors.grey,
              textColor: Colors.white,
              fontSize: 16.0,
            );
            setState(() {
        _isLoading = false; // Stop loading
      });

            // Navigate to another page
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(
                builder: (context) => TabsScreen(pageIndex: 1),
              ),
            );
          }
        } else {
          // If 'success' is false
          Fluttertoast.showToast(
            msg: responseData['message'] ?? "Failed to create user.",
            toastLength: Toast.LENGTH_SHORT,
            gravity: ToastGravity.BOTTOM,
            timeInSecForIosWeb: 2,
            backgroundColor: Colors.red,
            textColor: Colors.white,
            fontSize: 16.0,
          );
          setState(() {
        _isLoading = false; // Stop loading
      });
        }
      } else if (responses.statusCode == 422) {
        final responseData = jsonDecode(responses.body);

        if (responseData['validationError'] != null) {
          responseData['validationError'].forEach((key, value) {
            Fluttertoast.showToast(
              msg: value[0], // Display the first error message
              toastLength: Toast.LENGTH_SHORT,
              gravity: ToastGravity.BOTTOM,
              timeInSecForIosWeb: 2,
              backgroundColor: Colors.red,
              textColor: Colors.white,
              fontSize: 16.0,
            );
           setState(() {
        _isLoading = false; // Stop loading
      });
          });

        }
      } else {
        Fluttertoast.showToast(
          msg: "An error occurred",
          toastLength: Toast.LENGTH_SHORT,
          gravity: ToastGravity.BOTTOM,
          timeInSecForIosWeb: 2,
          backgroundColor: Colors.red,
          textColor: Colors.white,
          fontSize: 16.0,
        );
        setState(() {
        _isLoading = false; // Stop loading
      });
      }
    } catch (error) {
      print('Error: $error');
    }finally{
      setState(() {
        _isLoading = false; // Stop loading
      });
    }
  }

  @override
  void initState() {
    // isLogin();
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
                Row(
                  children: [
                    const SizedBox(
                      width: 20,
                    ),
                    GestureDetector(
                        onTap: () {
                          Navigator.of(context).pop();
                        },
                        child: Image.asset("assets/images/Back Button.png")),
                  ],
                ),
                const SizedBox(
                  height: 100,
                ),
                const Center(
                  child: Text(
                    'Sign Up',
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
                      'Name',
                    ),
                    controller: _nameController,
                    // keyboardType: TextInputType.emailAddress,
                    validator: (value) {
                      if (value!.isEmpty) {
                        return 'Please enter your full name';
                      }
                      return null;
                    },
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
                    validator: (value) {
                      if (value!.isEmpty) {
                        return 'Please enter min 8 charecter password';
                      }
                      if (value.length < 8) {
                        return 'Password must be exactly 8 characters long';
                      }
                      return null;
                    },
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
                const SizedBox(
                  height: 10,
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 20.0, vertical: 5.0),
                  child: TextFormField(
                    style: const TextStyle(color: Colors.black),
                    keyboardType: TextInputType.text,
                    controller: _conPasswordController,
                    onSaved: (input) {
                      setState(() {
                        _conPasswordController.text = input as String;
                      });
                    },
                    validator: (value) {
                      if (value!.isEmpty) {
                        return 'Please enter your password again';
                      }
                      if (value != _passwordController.text) {
                        return 'Passwords do not match';
                      }
                      return null;
                    },
                    obscureText: hideConPassword,
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
                      hintText: "Confirm Password",
                      fillColor: kInputBoxBackGroundColor,
                      contentPadding: const EdgeInsets.symmetric(
                          vertical: 18, horizontal: 15),
                      suffixIcon: IconButton(
                        onPressed: () {
                          setState(() {
                            hideConPassword = !hideConPassword;
                          });
                        },
                        color: kInputBoxIconColor,
                        icon: Icon(hideConPassword
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
                                  onPressed: () async {
                                    if (globalFormKey.currentState!
                                        .validate()) {
                                      signup(
                                          _nameController.text,
                                          _emailController.text,
                                          _passwordController.text,
                                          _conPasswordController.text,
                                          context);
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
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                     _isLoading ? CircularProgressIndicator():
                                      Text(
                                        'Sign Up',
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
              ],
            ),
          ),
        ),
      ),
    );
  }
}
