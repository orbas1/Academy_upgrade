import 'package:flutter/material.dart';
import './custom_text.dart';
import '../constants.dart';

class TabViewDetails extends StatelessWidget {
  final String? titleText;
  final List<String>? listText;

  const TabViewDetails({
    super.key,
    @required this.titleText,
    @required this.listText,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: <Widget>[
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: <Widget>[
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 5, horizontal: 10),
              child: CustomText(
                text: titleText,
                fontSize: 18,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        Expanded(
          child: ListView.builder(
            shrinkWrap: true,
            physics: const ClampingScrollPhysics(),
            itemCount: listText!.length,
            itemBuilder: (ctx, index) {
              return Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    const SizedBox(height: 5),
                    CustomText(
                      text: listText![index],
                      colors: kGreyLightColor,
                      fontSize: 15,
                      fontWeight: FontWeight.w400,
                    ),
                    if((listText!.length - 1) != index)
                    const SizedBox(height: 5),
                    if((listText!.length - 1) != index)
                    Divider(color: kGreyLightColor.withOpacity(0.3)),
                  ],
                ),
              );
            },
          ),
        ),
      ],
    );
  }
}
