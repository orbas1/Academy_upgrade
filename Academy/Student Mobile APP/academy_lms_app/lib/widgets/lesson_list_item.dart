import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import '../constants.dart';
import '../models/lesson.dart';

class LessonListItem extends StatefulWidget {
  final Lesson? lesson;
  final int courseId;

  const LessonListItem(
      {super.key, @required this.lesson, required this.courseId});

  @override
  State<LessonListItem> createState() => _LessonListItemState();
}

class _LessonListItemState extends State<LessonListItem> {
  void lessonAction(Lesson lesson) async {
    // if (lesson.lessonType == 'video') {
    //   if (lesson.videoTypeWeb == 'system' ||
    //       lesson.videoTypeWeb == 'html5' ||
    //       lesson.videoTypeWeb == 'amazon') {
    //     Navigator.push(
    //       context,
    //       MaterialPageRoute(
    //           builder: (context) => PlayVideoFromNetwork(
    //               courseId: widget.courseId,
    //               lessonId: lesson.id!,
    //               videoUrl: lesson.videoUrlWeb!)),
    //     );
    //   } else if (lesson.videoTypeWeb == 'Vimeo') {
    //     String vimeoVideoId = lesson.videoUrlWeb!.split('/').last;
    //     // Navigator.push(
    //     //     context,
    //     //     MaterialPageRoute(
    //     //       builder: (context) => PlayVideoFromVimeoId(
    //     //           courseId: widget.courseId,
    //     //           lessonId: lesson.id!,
    //     //           vimeoVideoId: vimeoVideoId),
    //     //     ));
    //     String vimUrl = 'https://player.vimeo.com/video/$vimeoVideoId';
    //     Navigator.push(
    //         context,
    //         MaterialPageRoute(
    //             builder: (context) =>
    //                 VimeoIframe(url: vimUrl)));
                    
    //   } else {
    //     Navigator.push(
    //         context,
    //         MaterialPageRoute(
    //           builder: (context) => PlayVideoFromYoutube(
    //               courseId: widget.courseId,
    //               lessonId: lesson.id!,
    //               videoUrl: lesson.videoUrlWeb!),
    //         ));
    //   }
    // }
  }

  getLessonIcon(String lessonType) {
    // print(lessonType);
    if (lessonType == 'video-url' ||
        lessonType == 'vimeo-url' ||
        lessonType == 'google_drive' ||
        lessonType == 'system-video') {
      return SvgPicture.asset('assets/icons/video.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else if (lessonType == 'quiz') {
      return SvgPicture.asset('assets/icons/book.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else {
      return SvgPicture.asset('assets/icons/iframe.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 5.0, vertical: 7.0),
      child: Row(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4.0),
            child: getLessonIcon(widget.lesson!.lessonType.toString()),
          ),
          Expanded(
            flex: 1,
            child: Padding(
              padding: const EdgeInsets.only(left: 5.0),
              child: Text(widget.lesson!.title.toString(),
                  style:
                      const TextStyle(fontSize: 14, color: kGreyLightColor)),
            ),
          ),
          if (widget.lesson!.isFree == 1)
            InkWell(
              onTap: () {
                lessonAction(widget.lesson!);
              },
              child: const Row(
                children: [
                  Icon(
                    Icons.remove_red_eye_outlined,
                    size: 17,
                    color: kDefaultColor,
                  ),
                  Text(
                    ' Preview',
                    style: TextStyle(
                      color: kDefaultColor,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
