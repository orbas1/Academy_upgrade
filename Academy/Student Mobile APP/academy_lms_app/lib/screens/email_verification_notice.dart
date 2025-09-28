// ignore_for_file: prefer_const_constructors

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';

class EmailVerificationNotice extends StatefulWidget {
  const EmailVerificationNotice({super.key});

  @override
  State<EmailVerificationNotice> createState() =>
      _EmailVerificationNoticeState();
}

class _EmailVerificationNoticeState extends State<EmailVerificationNotice> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
          child: SingleChildScrollView(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(20.0),
            child: Column(
              // crossAxisAlignment: CrossAxisAlignment.center,
              // mainAxisAlignment: MainAxisAlignment.center,
              children: [
                SizedBox(
                  height: 100,
                ),
                Image.asset(
                  "assets/images/forgot_image.png",
                  height: 80,
                  width: 80,
                ),
                SizedBox(
                  height: 30,
                ),
                Text(
                  "Email Verification",
                  style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 28,
                      color: Colors.black,
                      fontWeight: FontWeight.w500),
                ),
                SizedBox(
                  height: 10,
                ),
                Text(
                  "A link is send to your Email",
                  style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      color: kGreyColor,
                      fontWeight: FontWeight.w600),
                ),
                Text(
                  "Please check your Email..",
                  style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      color: Colors.black54,
                      fontWeight: FontWeight.w400),
                ),
                SizedBox(
                  height: 70,
                ),
                Text(
                  "if verify is completed",
                  style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 18,
                      color: Colors.black,
                      fontWeight: FontWeight.w500),
                ),
                SizedBox(
                  height: 10,
                ),
                Text(
                  "then",
                  style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      color: Colors.black54,
                      fontWeight: FontWeight.w500),
                ),
                SizedBox(
                  height: 10,
                ),
                GestureDetector(
                  onTap: () {
                    // Navigator.of(context).pushAndRemoveUntil(
                    //     MaterialPageRoute(builder: (context) => LoginScreen()),
                    //     (route) => false);
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(
                        builder: (context) => TabsScreen(pageIndex: 1),
                      ),
                    );
                  },
                  child: Container(
                    width: MediaQuery.sizeOf(context).width,
                    height: 55,
                    decoration: BoxDecoration(
                      color: kDefaultColor,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Center(
                      child: Text("Log in",
                          style: TextStyle(
                              fontSize: 16,
                              color: Colors.white,
                              fontWeight: FontWeight.w500)),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      )),
    );
  }
}
