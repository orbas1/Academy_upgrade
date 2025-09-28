import 'dart:convert';

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/forget_password_notice.dart';
import 'package:flutter/material.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ForgetPasswordScreen extends StatefulWidget {
  const ForgetPasswordScreen({super.key});

  @override
  State<ForgetPasswordScreen> createState() => _ForgetPasswordScreenState();
}

class _ForgetPasswordScreenState extends State<ForgetPasswordScreen> {
  GlobalKey<FormState> globalFormKey = GlobalKey<FormState>();
  // final scaffoldKey = GlobalKey<ScaffoldState>();

  bool _isLoading = false;
  final _emailController = TextEditingController();
  SharedPreferences? sharedPreferences;

  // Future<void> sendemail(
  //   String email,
  // ) async {
  //   sharedPreferences = await SharedPreferences.getInstance();
  //   dynamic tokens = sharedPreferences!.getString("access_token");

  //   var urls = "$baseUrl/api/forgot_password";
  //   try {
  //     final responses = await http.post(
  //       Uri.parse(urls),
  //       headers: {
  //         'Content-Type': 'application/json',
  //         'Accept': 'application/json',
  //         'Authorization': 'Bearer $tokens',
  //       },
  //       body: json.encode({
  //         'email': email,
  //       }),
  //     );
  //     print(responses.body);
  //     if (responses.statusCode == 200) {
  //       print(responses.body);
  //       Fluttertoast.showToast(
  //         msg: "Email sended, Please check your email",
  //         toastLength: Toast.LENGTH_SHORT,
  //         gravity: ToastGravity.BOTTOM,
  //         timeInSecForIosWeb: 1,
  //         backgroundColor: Colors.grey,
  //         textColor: Colors.white,
  //         fontSize: 16.0,
  //       );
  //     } else {
  //       Fluttertoast.showToast(
  //         msg: "Here is something error",
  //         toastLength: Toast.LENGTH_SHORT,
  //         gravity: ToastGravity.BOTTOM,
  //         timeInSecForIosWeb: 1,
  //         backgroundColor: Colors.grey,
  //         textColor: Colors.white,
  //         fontSize: 16.0,
  //       );
  //     }
  //   } catch (error) {
  //     // rethrow;
  //     print('Error: $error');
  //   }
  // }

  Future<void> sendemail(String email, context) async {
    setState(() {
      _isLoading = true;
    });
    SharedPreferences sharedPreferences = await SharedPreferences.getInstance();
    dynamic tokens = sharedPreferences.getString("access_token");

    var urls = "$baseUrl/api/forgot_password";
    try {
      final responses = await http.post(
        Uri.parse(urls),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $tokens',
        },
        body: json.encode({
          'email': email,
        }),
      );

      if (responses.statusCode == 200) {
        var responseData = jsonDecode(responses.body);
        if (responseData['success'] == true) {
          Fluttertoast.showToast(
            msg: responseData['message'],
            toastLength: Toast.LENGTH_SHORT,
            gravity: ToastGravity.BOTTOM,
            backgroundColor: Colors.greenAccent,
            textColor: Colors.white,
            fontSize: 16.0,
          );
          await Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(
                  builder: (context) => FogotPasswordVerificationNotice()),
              (route) => false);
        } else {
          Fluttertoast.showToast(
            msg: "Error: ${responseData['message']}",
            toastLength: Toast.LENGTH_SHORT,
            gravity: ToastGravity.BOTTOM,
            backgroundColor: Colors.red,
            textColor: Colors.white,
            fontSize: 16.0,
          );
        }
      } else {
        Fluttertoast.showToast(
          msg: "There is an error. Please try again.",
          toastLength: Toast.LENGTH_SHORT,
          gravity: ToastGravity.BOTTOM,
          backgroundColor: Colors.red,
          textColor: Colors.white,
          fontSize: 16.0,
        );
      }
    } catch (error) {
      print('Error: $error');
    }
    setState(() {
      _isLoading = false;
    });
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
      backgroundColor: kBackGroundColor,
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
                    'Foeget Password',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                const SizedBox(
                  height: 40,
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 20.0),
                  child: Text(
                    'Provide your email address to reset password ',
                    style: TextStyle(
                      color: kGreyLightColor,
                      fontSize: 16,
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
                      _emailController.text = value as String;
                    },
                  ),
                ),
                SizedBox(
                  width: MediaQuery.sizeOf(context).width,
                  child: Padding(
                    padding: const EdgeInsets.only(top: 10.0),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 20.0, vertical: 15.0),
                      child: MaterialButton(
                        minWidth: MediaQuery.sizeOf(context).width,
                        elevation: 0,
                        color: kDefaultColor,
                        onPressed: () {
                          if (globalFormKey.currentState!.validate()) {
                            if (!_isLoading) {
                              sendemail(_emailController.text, context);
                            }
                          }
                        },
                        padding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadiusDirectional.circular(16),
                        ),
                        child: _isLoading
                            ? const Center(
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                      Colors.white),
                                ),
                              )
                            : const Text(
                                'Reset Password',
                                style: TextStyle(
                                  fontSize: 16,
                                  color: Colors.white,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                      ),
                    ),
                  ),
                ),
                // SizedBox(
                //   width: MediaQuery.sizeOf(context).width,
                //   child: Padding(
                //     padding: const EdgeInsets.only(top: 10.0),
                //     child: Padding(
                //       padding: const EdgeInsets.symmetric(
                //           horizontal: 20.0, vertical: 15.0),
                //       child: MaterialButton(
                //         minWidth: MediaQuery.sizeOf(context).width,
                //         elevation: 0,
                //         color: kDefaultColor,
                //         onPressed: () {
                //           if (globalFormKey.currentState!.validate()) {
                //             // Navigator.of(context).pushAndRemoveUntil(
                //             //     MaterialPageRoute(
                //             //         builder: (context) =>
                //             //             FogotPasswordVerificationNotice()),
                //             //     (route) => false);

                //             _isLoading
                //                 ? const Center(
                //                     child: CircularProgressIndicator())
                //                 : sendemail(_emailController.text, context);
                //           }
                //         },
                //         padding: const EdgeInsets.symmetric(
                //             horizontal: 20, vertical: 16),
                //         shape: RoundedRectangleBorder(
                //           borderRadius: BorderRadiusDirectional.circular(16),
                //           // side: const BorderSide(color: kRedColor),
                //         ),
                //         child: const Row(
                //           mainAxisAlignment: MainAxisAlignment.center,
                //           children: [
                //             Text(
                //               'Reset Password',
                //               style: TextStyle(
                //                 fontSize: 16,
                //                 color: Colors.white,
                //                 fontWeight: FontWeight.w500,
                //               ),
                //             ),
                //           ],
                //         ),
                //       ),
                //     ),
                //   ),
                // ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
