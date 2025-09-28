// ignore_for_file: unused_element, prefer_interpolation_to_compose_strings, avoid_print, must_be_immutable

import 'dart:io' show Platform;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
// import 'package:zoom_allinonesdk/data/util/zoom_error.dart';
// import 'package:zoom_allinonesdk/zoom_allinonesdk.dart';

class JoinMeetingScreen extends StatefulWidget {
  String meetingId;
  String meetingPass;
  String meetingClientKey;
  String meetingClientSecret;
  JoinMeetingScreen(
      {required this.meetingId,
      required this.meetingPass,
      required this.meetingClientKey,
      required this.meetingClientSecret,
      super.key});

  @override
  State<JoinMeetingScreen> createState() => _JoinMeetingScreenState();
}

class _JoinMeetingScreenState extends State<JoinMeetingScreen> {
  bool flag = false;

  @override
  void initState() {
    super.initState();
    platformCheck(widget.meetingId, widget.meetingPass);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: true,
      appBar: AppBar(
        title: const Text('Join Meeting'),
      ),
      body: const SingleChildScrollView(
        child: Center(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Text('Please Wait'),
              CircularProgressIndicator(),
            ],
          ),
        ),
      ),
    );
  }

  void platformCheck(String meetingId, String password) {
    if (Platform.isAndroid || Platform.isIOS) {
      joinMeetingAndroidAndIos(meetingId, password);
    }
  }

  void joinMeetingAndroidAndIos(String meetingId, String password) async {
    // ZoomOptions zoomOptions = ZoomOptions(
    //   domain: "zoom.us",
    //   clientId: widget.meetingClientKey,
    //   clientSecert: widget.meetingClientSecret,
    // );
    // var meetingOptions = MeetingOptions(
    //     displayName: "", meetingId: meetingId, meetingPassword: password);

    // var zoom = ZoomAllInOneSdk();
    // try {
    //   var results = await zoom.initZoom(zoomOptions: zoomOptions);
    //   if (results[0] == 0) {
    //     try {
    //       var loginResult =
    //           await zoom.joinMeting(meetingOptions: meetingOptions);
    //       print("Successfully joined meeting with result: $loginResult");
    //     } catch (error) {
    //       if (error is ZoomError) {
    //         print("[ZoomError during join] : ${error.message}");
    //       } else {
    //         print("[Error Generated during join] : $error");
    //       }
    //     }
    //   } else {
    //     print("Initialization failed with result: ${results[0]}");
    //   }
    // } catch (error) {
    //   if (error is ZoomError) {
    //     print("[ZoomError during init] : ${error.message}");
    //   } else {
    //     print("[Error Generated during init] : $error");
    //   }
    // }
  }

  void _showSnackBar(BuildContext context) {
    final snackBar = SnackBar(
      content: const Text('Please fill all the empty fields'),
      action: SnackBarAction(
        label: 'Close',
        onPressed: () {},
      ),
    );

    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
}

Widget getDefaultTextFieldWithLabel(
  BuildContext context,
  String label,
  TextEditingController textEditingController, {
  bool withSuffix = false,
  bool minLines = false,
  bool isPassword = false,
  bool isEnabled = true,
  bool isPrefix = false,
  Widget? prefix,
  double? height,
  IconData? suffixImage,
  Function? imageFunction,
  List<TextInputFormatter>? inputFormatters,
  FormFieldValidator<String>? validator,
  BoxConstraints? constraint,
  ValueChanged<String>? onChanged,
  double vertical = 20,
  double horizontal = 20,
  int? maxLength,
  String obscuringCharacter = '•',
  GestureTapCallback? onTap,
  bool isReadOnly = false,
  TextInputType? keyboardType,
}) {
  return StatefulBuilder(
    builder: (context, setState) {
      return TextFormField(
        readOnly: isReadOnly,
        onTap: onTap,
        onChanged: onChanged,
        validator: validator,
        enabled: isEnabled,
        keyboardType: keyboardType,
        inputFormatters: inputFormatters,
        maxLines: minLines ? null : 1,
        controller: textEditingController,
        obscuringCharacter: obscuringCharacter,
        autofocus: false,
        obscureText: isPassword,
        showCursor: true,
        cursorColor: const Color(0xFF23408F),
        maxLength: maxLength,
        autovalidateMode: AutovalidateMode.onUserInteraction,
        style: const TextStyle(
          color: Colors.black,
          fontWeight: FontWeight.w700,
          fontSize: 15,
        ),
        decoration: InputDecoration(
          counterText: "",
          contentPadding:
              EdgeInsets.symmetric(vertical: vertical, horizontal: horizontal),
          isDense: true,
          filled: true,
          fillColor: Colors.white,
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFDEDEDE), width: 1),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFF23408F), width: 1),
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFDEDEDE), width: 1),
          ),
          suffixIconConstraints: const BoxConstraints(
            maxHeight: 24,
          ),
          suffixIcon: withSuffix
              ? GestureDetector(
                  onTap: () {
                    imageFunction!();
                  },
                  child: Padding(
                    padding: const EdgeInsets.only(right: 18),
                    child: Icon(suffixImage, size: 20.0),
                  ),
                )
              : null,
          prefixIconConstraints: constraint,
          prefixIcon: isPrefix ? prefix : null,
          labelText: label,
          labelStyle: const TextStyle(
            color: Color(0xFF9B9B9B),
            fontWeight: FontWeight.w700,
            fontSize: 15,
          ),
        ),
      );
    },
  );
}
