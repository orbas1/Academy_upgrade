
import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class VimeoIframe extends StatefulWidget {
  static const routeName = '/vimeo-iframe';
  
  final String? url;

  const VimeoIframe({super.key, required this.url});

  @override
  State<VimeoIframe> createState() => _VimeoIframeState();
}

class _VimeoIframeState extends State<VimeoIframe> {
  // final Completer<WebViewController> _controller =
  //     Completer<WebViewController>();

  late final WebViewController _controller;
  var loadingPercentage = 0;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..loadRequest(
        Uri.dataFromString('''<iframe 
                src="${widget.url}?loop=0&autoplay=0" 
                width="100%" height="100%" frameborder="0" allow="fullscreen" 
                allowfullscreen></iframe>''',
            mimeType: 'text/html'),
      );
  }

  @override
  Widget build(BuildContext context) {

    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: Stack(
        children: [
          WebViewWidget(
            controller: _controller,
          ),
        ],
      ),
    );
  }
}
