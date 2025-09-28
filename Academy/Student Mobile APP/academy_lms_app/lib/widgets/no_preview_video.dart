import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';

class NoPreviewVideo extends StatefulWidget {
  const NoPreviewVideo({super.key});

  @override
  State<NoPreviewVideo> createState() => _NoPreviewVideoState();
}

class _NoPreviewVideoState extends State<NoPreviewVideo> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBarOne(
        logo: "light_logo.png",
        title: "Academy",
       
      ),
      body: Center(
        child: SizedBox(
          width: 300,
          child: Text(
            "No preview video is available for this course",
            textAlign: TextAlign.center,
            style: TextStyle(
                color: Colors.black,
                fontFamily: 'Inter',
                fontSize: 24,
                fontWeight: FontWeight.w600),
          ),
        ),
      ),
    );
  }
}
