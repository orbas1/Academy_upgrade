import 'package:flutter/material.dart';

import '../constants.dart';

class AppBarTwo extends StatefulWidget implements PreferredSizeWidget {
  @override
  final Size preferredSize;
  final dynamic title;
  final dynamic logo;

  const AppBarTwo({super.key, this.title, this.logo})
      : preferredSize = const Size.fromHeight(70.0);

  @override
  State<AppBarTwo> createState() => _AppBarTwoState();
}

class _AppBarTwoState extends State<AppBarTwo> {

  @override
  void initState() {
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return AppBar(
      backgroundColor: kBackGroundColor,
      toolbarHeight: 70,
      leadingWidth: 80,
      leading: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 15.0, vertical: 10),
        child: GestureDetector(
          child: Card(
            color: kBackGroundColor,
            elevation: 0,
            borderOnForeground: true,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10.0),
              side: BorderSide(
                color: kBackButtonBorderColor.withOpacity(0.1),
                width: 1.0,
              ),
            ),
            child: const Padding(
              padding: EdgeInsets.only(left: 8.0),
              child: Icon(
                Icons.arrow_back_ios,
                color: kBlackColor,
                size: 18,
              ),
            ),
          ),
          onTap: () {
            Navigator.pop(context);
          },
        ),
      ),
      centerTitle: true,
      title: widget.title != null
        ? Text(
            widget.title,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w500,
            ),
          )
        : (widget.logo != null
            ? Image.asset(
                'assets/images/${widget.logo}',
                height: 34.27,
                width: 153.6,
              )
            : const Text('')),
    );
  }
}