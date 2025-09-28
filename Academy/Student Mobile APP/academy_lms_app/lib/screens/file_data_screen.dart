import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import '../constants.dart';
import '../widgets/appbar_one.dart';

class FileDataScreen extends StatefulWidget {
  static const routeName = '/file-data';
  final String attachment;
  final String note;
  const FileDataScreen({super.key, required this.attachment, required this.note});

  @override
  // ignore: library_private_types_in_public_api
  _FileDataScreenState createState() => _FileDataScreenState();
}

class _FileDataScreenState extends State<FileDataScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(title: 'Text Lesson'),
      backgroundColor: kBackgroundColor,
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 15.0, vertical: 10),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Html(
                data: widget.attachment,
              ),
              Padding(
                padding: const EdgeInsets.symmetric(
                  vertical: 10.0,
                ),
                child: Container(
                  width: double.infinity,
                  color: kBackgroundColor,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8.0, vertical: 8.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          "Note: ",
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Html(
                          data: widget.note,
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
    );
  }
}
