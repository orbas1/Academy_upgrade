import 'package:flutter/material.dart';

import '../widgets/appbar_one.dart';

class ImageViewrScreen extends StatefulWidget {
  static const routeName = '/image-data';
  final String? url;
  const ImageViewrScreen({super.key, required this.url});

  @override
  State<ImageViewrScreen> createState() => _ImageViewrScreenState();
}

class _ImageViewrScreenState extends State<ImageViewrScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(title: 'Image View'),
      body: SingleChildScrollView(
        child: Image.network(widget.url!),
      ),
    );
  }
}