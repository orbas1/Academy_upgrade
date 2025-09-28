
// import 'package:flutter/material.dart';
// import 'package:vimeo_video_player/vimeo_video_player.dart';
// import 'package:webview_flutter/webview_flutter.dart';

// class VideoPage extends StatefulWidget {
//   final String? videoId;
//   const VideoPage({super.key, this.videoId});

//   @override
//   _VideoPageState createState() => _VideoPageState();
// }

// class _VideoPageState extends State<VideoPage> {
//   late final WebViewController _controller;

//   @override
//   void initState() {
//     super.initState();
//     // _controller = WebViewController()
//     //   ..setJavaScriptMode(JavaScriptMode.unrestricted)
//     //   ..loadRequest(Uri.parse(_getVimeoEmbedUrl()));
//   }

//   // Function to construct Vimeo embed URL
//   String _getVimeoEmbedUrl() {
//     final videoId = widget.videoId;
//     // return "https://vimeo.com/$videoId";
//     return "https://player.vimeo.com/video/$videoId";
//   }

//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//         appBar: AppBar(title: const Text('Vimeo Video Player')),
//         body:
//          VimeoVideoPlayer(
//           url: _getVimeoEmbedUrl(),
//         )
//         //  WebViewWidget(
//         //   controller: _controller,
//         // ),
//         );
//   }
// }

// import 'package:flutter/material.dart';
// import 'package:vimeo_embed_player/vimeo_embed_player.dart';

// void main() {
//   runApp(const MyApp());
// }

// class MyApp extends StatelessWidget {
//   const MyApp({super.key});

//   @override
//   Widget build(BuildContext context) {
//     return const MaterialApp(
//       debugShowCheckedModeBanner: false,
//       home: AspectRatio(
//         aspectRatio: 16.0 / 9.0,
//         child: VimeoEmbedPlayer(
//           vimeoId: '397912933',
//           autoPlay: true,
//         ),
//       ),
//     );
//   }
// }

