// import 'package:flutter/material.dart';

// class GradientBackground extends StatelessWidget {
//   final Widget child;

//   const GradientBackground({super.key, required this.child});

//   @override
//   Widget build(BuildContext context) {
//     return Align(
//       alignment: Alignment.bottomRight,
//       child: Container(
//         height: MediaQuery.of(context).size.height,
//         width: MediaQuery.of(context).size.width,
//         decoration: BoxDecoration(
//           gradient: LinearGradient(
//             colors: [
//               const Color(0xFFFF31C2).withOpacity(0.07),
//               const Color(0xFFFFA42E).withOpacity(0),
//             ],
//             stops: const [0.0, 1.0],
//             begin: Alignment.bottomRight,
//             end: Alignment.centerLeft,
//           ),
//         ),
//         child: child,
//       ),
//     );
//   }
// }