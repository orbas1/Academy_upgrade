// ignore_for_file: deprecated_member_use, use_build_context_synchronously, avoid_print

import 'package:academy_lms_app/features/storage/data/storage_recovery_service.dart';
import 'package:academy_lms_app/screens/course_detail.dart';
import 'package:academy_lms_app/screens/image_viewer_Screen.dart';
import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:academy_lms_app/widgets/from_vimeo_player.dart';
import 'package:academy_lms_app/widgets/new_youtube_player.dart';
import 'package:academy_lms_app/widgets/vimeo_iframe.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:html_unescape/html_unescape.dart';
import 'package:percent_indicator/percent_indicator.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../constants.dart';
import '../models/lesson.dart';
import '../providers/my_courses.dart';
import '../widgets/common_functions.dart';
import '../widgets/from_network.dart';
import '../widgets/live_class_tab_widget.dart';
import 'file_data_screen.dart';
// import 'meeting_screen.dart';
import 'webview_screen_iframe.dart';
  import 'package:http/http.dart' as http;


class MyCourseDetailScreen extends StatefulWidget {
  final int courseId;
  final String enableDripContent;
  const MyCourseDetailScreen(
      {super.key, required this.courseId, required this.enableDripContent});

  @override
  State<MyCourseDetailScreen> createState() => _MyCourseDetailScreenState();
}

class _MyCourseDetailScreenState extends State<MyCourseDetailScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  late ScrollController _scrollController;

  int? selected;
  var _isInit = true;
  var _isLoading = false;
  // ignore: unused_field
  Lesson? _activeLesson;
  dynamic data;

  String downloadId = "";

  dynamic path;
  dynamic fileName;
  dynamic lessonId;
  dynamic courseId;
  dynamic sectionId;
  dynamic courseTitle;
  dynamic sectionTitle;
  dynamic thumbnail;

  final StorageRecoveryService _storageRecoveryService =
      StorageRecoveryService();

  @override
  void initState() {
    super.initState();
    _scrollController = ScrollController();
    _scrollController.addListener(_scrollListener);
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(_smoothScrollToTop);
    _tabController.addListener(_smoothScrollToTop);

    super.initState();
  }

  @override
  void dispose() {
    _tabController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  _scrollListener() {}

  _smoothScrollToTop() {
    _scrollController.animateTo(0,
        duration: const Duration(microseconds: 300), curve: Curves.ease);
  }

  @override
  void didChangeDependencies() {
    if (_isInit) {
      setState(() {
        _isLoading = true;
      });

      Provider.of<MyCourses>(context, listen: false)
          .fetchCourseSections(widget.courseId)
          .then((_) {
        final activeSections =
            Provider.of<MyCourses>(context, listen: false).sectionItems;
        setState(() {
          _isLoading = false;
          _activeLesson = activeSections.first.mLesson!.first;
        });
      });
    }
    _isInit = false;
    super.didChangeDependencies();
  }

Future<String> getGoogleDriveDownloadUrl(String fileId) async {
  try {
    // Initial request to get the confirmation token
    final initialUrl = 'https://drive.google.com/uc?export=download&id=$fileId';
    final response = await http.get(Uri.parse(initialUrl));

    // Check if a confirmation token is needed
    if (response.headers.containsKey('set-cookie')) {
      final cookies = response.headers['set-cookie']!;
      final tokenMatch = RegExp(r'confirm=([0-9A-Za-z\-_]+)').firstMatch(cookies);

      if (tokenMatch != null) {
        final token = tokenMatch.group(1)!;

        // Generate the confirmed URL
        return 'https://drive.google.com/uc?export=download&id=$fileId&confirm=$token';
      }
    }

    // If no token is required, return the original URL
    return initialUrl;
  } catch (e) {
    throw Exception('Failed to generate download URL: $e');
  }
}


  void lessonAction(Lesson lesson) async {
    if (lesson.lessonType == 'text') {
      Navigator.push(
          context,
          MaterialPageRoute(
              builder: (context) => FileDataScreen(
                  attachment: lesson.attachment!, note: lesson.summary!)));
    } else if (lesson.lessonType == 'iframe') {
      final url = lesson.videoUrl;
      Navigator.push(
          context,
          MaterialPageRoute(
              builder: (context) => WebViewScreenIframe(url: url)));
    } else if (lesson.lessonType == 'quiz') {
      Fluttertoast.showToast(
        msg:
            "This option is not available on Mobile Phone, Please go to the Browser",
        toastLength: Toast.LENGTH_LONG,
        gravity: ToastGravity.BOTTOM,
        backgroundColor: Colors.redAccent,
        textColor: Colors.white,
        timeInSecForIosWeb: 15,
        fontSize: 16.0,
      );
      // final url = lesson.videoUrl;
      // Navigator.push(
      //     context,
      //     MaterialPageRoute(
      //         builder: (context) => WebViewScreenIframe(url: url)));
    } else if (lesson.lessonType == 'image') {
      final url = lesson.attachmentUrl;
      Navigator.push(context,
          MaterialPageRoute(builder: (context) => ImageViewrScreen(url: url)));
    } else if (lesson.lessonType == 'document_type') {
      final url = lesson.attachmentUrl;
      _launchURL(url);
    } else {
      if (lesson.lessonType == 'system-video') {
        Navigator.push(
          context,
          MaterialPageRoute(
              builder: (context) => PlayVideoFromNetwork(
                  courseId: widget.courseId,
                  lessonId: lesson.id!,
                  videoUrl: lesson.videoUrl!)),
        );
      } else if (lesson.lessonType == 'google_drive') {
        final RegExp regExp = RegExp(r'[-\w]{25,}');
        final Match? match = regExp.firstMatch(lesson.videoUrl.toString());
        final fileId = match!.group(0)!;

        print(lesson.videoUrl);
        print(match);

        // String url =
        //     'https://drive.google.com/uc?export=download&id=${match!.group(0)}';
    //     String url =
    // 'https://drive.google.com/uc?export=view&id=${match!.group(0)}';
        String url =
    "https://www.googleapis.com/drive/v3/files/$fileId?alt=media";

        print(url);

        Navigator.push(
          context,
          MaterialPageRoute(
              builder: (context) => PlayVideoFromNetwork(
                  courseId: widget.courseId,
                  lessonId: lesson.id!,
                  videoUrl: url)),
        );
      } else if (lesson.lessonType == 'html5') {
        // final RegExp regExp = RegExp(r'[-\w]{25,}');
        // final Match? match = regExp.firstMatch(lesson.videoUrl.toString());

        // print(match);

        // String url =
        //     'https://drive.google.com/uc?export=download&id=${match!.group(0)}';

        Navigator.push(
          context,
          MaterialPageRoute(
              builder: (context) => PlayVideoFromNetwork(
                  courseId: widget.courseId,
                  lessonId: lesson.id!,
                  videoUrl: lesson.videoUrl!)),
        );
      } else if (lesson.lessonType == 'vimeo-url') {
        // print(lesson.videoUrl);
        String vimeoVideoId = lesson.videoUrl!.split('/').last;
        // AspectRatio(
        //   aspectRatio: 16.0 / 9.0,
        //   child: VimeoEmbedPlayer(
        //     vimeoId: vimeoVideoId,
        //     autoPlay: true,
        //   ),
        // );

        showDialog(
          context: context,
          builder: (BuildContext context) {
            return AlertDialog(
              backgroundColor: kBackgroundColor,
              titlePadding: EdgeInsets.zero,
              title: const Padding(
                padding: EdgeInsets.only(left: 15.0, right: 15, top: 20),
                child: Center(
                  child: Text('Choose Video player',
                      style:
                          TextStyle(fontSize: 20, fontWeight: FontWeight.w600)),
                ),
              ),
              actions: <Widget>[
                const SizedBox(
                  height: 20,
                ),
                MaterialButton(
                  elevation: 0,
                  color: kPrimaryColor,
                  onPressed: () {
                    String vimUrl =
                        'https://player.vimeo.com/video/$vimeoVideoId';
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (context) => VimeoIframe(url: vimUrl)));
                  },
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadiusDirectional.circular(6),
                    // side: const BorderSide(color: kPrimaryColor),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Vimeo Iframe',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(
                  height: 10,
                ),
                // MaterialButton(
                //   elevation: 0,
                //   color: kPrimaryColor,
                //   onPressed: () {
                //     Navigator.push(
                //         context,
                //         MaterialPageRoute(
                //           builder: (context) => PlayVideoFromVimeoId(
                //               courseId: widget.courseId,
                //               lessonId: lesson.id!,
                //               vimeoVideoId: vimeoVideoId),
                //         ));
                //   },
                //   padding:
                //       const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                //   shape: RoundedRectangleBorder(
                //     borderRadius: BorderRadiusDirectional.circular(6),
                //     // side: const BorderSide(color: kPrimaryColor),
                //   ),
                //   child: const Row(
                //     mainAxisAlignment: MainAxisAlignment.center,
                //     children: [
                //       Text(
                //         'Vimeo Pro',
                //         style: TextStyle(
                //           fontSize: 16,
                //           color: Colors.white,
                //           fontWeight: FontWeight.w500,
                //         ),
                //       ),
                //     ],
                //   ),
                // ),
                // const SizedBox(height: 10),
                MaterialButton(
                  elevation: 0,
                  color: kPrimaryColor,
                  onPressed: () {
                    // String vimUrl =
                    //     'https://player.vimeo.com/video/$vimeoVideoId';
                    // Navigator.push(
                    //     context,
                    //     MaterialPageRoute(
                    //       builder: (context) =>
                    //           WebViewScreenIframe(url: vimUrl),
                    //     ));
                    Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => FromVimeoPlayer(
                              courseId: widget.courseId,
                              lessonId: lesson.id!,
                              vimeoVideoId: vimeoVideoId),
                        ));
                  },
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadiusDirectional.circular(6),
                    // side: const BorderSide(color: kPrimaryColor),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Vimeo',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 10),
              ],
            );
          },
        );
      } else {
        print(lesson.videoUrl);
        print(lesson.lessonType);
        Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => YoutubeVideoPlayerFlutter(
                  courseId: widget.courseId,
                  lessonId: lesson.id!,
                  videoUrl: lesson.videoUrl!),
            ));
      }
    }
  }

  Future<void> _launchURL(String lessonUrl) async {
    final uri = Uri.tryParse(lessonUrl);

    if (uri == null) {
      await _handleArchivedAsset(lessonUrl, 'Invalid URL');
      return;
    }

    try {
      final canOpen = await canLaunchUrl(uri);
      if (!canOpen) {
        throw Exception('Launcher unavailable for $lessonUrl');
      }

      final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
      if (!launched) {
        throw Exception('Launcher rejected $lessonUrl');
      }
    } catch (error) {
      await _handleArchivedAsset(lessonUrl, error);
    }
  }

  Future<void> _handleArchivedAsset(String lessonUrl, Object error) async {
    final messenger = ScaffoldMessenger.of(context);

    try {
      final result =
          await _storageRecoveryService.requestRestoreFromUrl(lessonUrl);

      messenger.showSnackBar(
        SnackBar(
          content: Text(
            '${result.message} We\'ll notify you when it\'s ready (typically ${result.estimatedMinutes ~/ 60}h).',
          ),
          duration: const Duration(seconds: 6),
        ),
      );
    } catch (restoreError) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            'We couldn\'t open this file or schedule a restore. Please retry later. (${restoreError.toString()})',
          ),
          duration: const Duration(seconds: 6),
        ),
      );
    }
  }

  getLessonIcon(String lessonType) {
    // print(lessonType);
    if (lessonType == 'video-url' ||
        lessonType == 'vimeo-url' ||
        lessonType == 'google_drive' ||
        lessonType == 'system-video') {
      return SvgPicture.asset(
        'assets/icons/video.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else if (lessonType == 'quiz') {
      return SvgPicture.asset(
        'assets/icons/book.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else if (lessonType == 'text') {
      return SvgPicture.asset(
        'assets/icons/text.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else if (lessonType == 'document_type') {
      return SvgPicture.asset(
        'assets/icons/document.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    } else {
      return SvgPicture.asset(
        'assets/icons/iframe.svg',
        colorFilter: const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final myLoadedCourse = Provider.of<MyCourses>(context, listen: false)
        .findById(widget.courseId);
    final sections =
        Provider.of<MyCourses>(context, listen: false).sectionItems;

    lessonBody() {
      return SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            ListView.builder(
              key: Key('builder ${selected.toString()}'),
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: sections.length,
              itemBuilder: (ctx, index) {
                final section = sections[index];
                return Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 20.0, vertical: 5.0),
                  child: Container(
                    decoration: BoxDecoration(
                      boxShadow: [
                        BoxShadow(
                          color: kBackButtonBorderColor.withOpacity(0.05),
                          blurRadius: 25,
                          offset: const Offset(0, 0),
                        ),
                      ],
                    ),
                    child: Card(
                      elevation: 0.0,
                      child: ExpansionTile(
                        key: Key(index.toString()),
                        initiallyExpanded: index == selected,
                        onExpansionChanged: ((newState) {
                          if (newState) {
                            setState(() {
                              selected = index;
                            });
                          } else {
                            setState(() {
                              selected = -1;
                            });
                          }
                        }),
                        iconColor: kDefaultColor,
                        collapsedIconColor: kSelectItemColor,
                        trailing: Icon(
                          selected == index
                              ? Icons.keyboard_arrow_up_rounded
                              : Icons.keyboard_arrow_down_rounded,
                          size: 35,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadiusDirectional.circular(16),
                          side: const BorderSide(color: Colors.white),
                        ),
                        title: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 5.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Align(
                                alignment: Alignment.centerLeft,
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 5.0,
                                  ),
                                  child: Text(
                                    '${index + 1}. ${HtmlUnescape().convert(section.title.toString())}',
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ),
                              Padding(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 5.0),
                                child: Row(
                                  children: [
                                    Expanded(
                                      flex: 1,
                                      child: Container(
                                        decoration: BoxDecoration(
                                          color:
                                              kTimeBackColor.withOpacity(0.12),
                                          borderRadius:
                                              BorderRadius.circular(5),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          vertical: 5.0,
                                        ),
                                        child: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            section.totalDuration.toString(),
                                            style: const TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w400,
                                              color: kTimeColor,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(
                                      width: 10.0,
                                    ),
                                    Expanded(
                                      flex: 1,
                                      child: Container(
                                        decoration: BoxDecoration(
                                          color: kLessonBackColor
                                              .withOpacity(0.12),
                                          borderRadius:
                                              BorderRadius.circular(5),
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                            vertical: 5.0),
                                        child: Align(
                                          alignment: Alignment.center,
                                          child: Text(
                                            '${section.mLesson!.length} Lessons',
                                            style: const TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w400,
                                              color: kLessonColor,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ),
                                    const Expanded(
                                      flex: 1,
                                      child: Text(""),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        children: [
                          ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: section.mLesson!.length,
                            itemBuilder: (ctx, indexLess) {
                              final lesson = section.mLesson![indexLess];
                              return InkWell(
                                onTap: () {
                                  setState(() {
                                    _activeLesson = lesson;
                                  });
                                  lessonAction(_activeLesson!);
                                },
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 15.0),
                                  child: Column(
                                    children: [
                                      Row(
                                        children: [
                                          Checkbox(
                                              activeColor: kDefaultColor,
                                              value: lesson.isCompleted == '1'
                                                  ? true
                                                  : false,
                                              onChanged: (bool? value) {
                                                // print(value);

                                                setState(() {
                                                  lesson.isCompleted =
                                                      value! ? '1' : '0';
                                                  if (value) {
                                                    myLoadedCourse
                                                            .totalNumberOfCompletedLessons =
                                                        myLoadedCourse
                                                                .totalNumberOfCompletedLessons! +
                                                            1;
                                                  } else {
                                                    myLoadedCourse
                                                            .totalNumberOfCompletedLessons =
                                                        myLoadedCourse
                                                                .totalNumberOfCompletedLessons! -
                                                            1;
                                                  }
                                                  var completePerc = (myLoadedCourse
                                                              .totalNumberOfCompletedLessons! /
                                                          myLoadedCourse
                                                              .totalNumberOfLessons!) *
                                                      100;
                                                  myLoadedCourse
                                                          .courseCompletion =
                                                      completePerc.round();
                                                });
                                                Provider.of<MyCourses>(context,
                                                        listen: false)
                                                    .toggleLessonCompleted(
                                                        lesson.id!.toInt(),
                                                        value! ? 1 : 0)
                                                    .then((_) => CommonFunctions
                                                        .showSuccessToast(
                                                            'Course Progress Updated'));
                                              }),
                                          Padding(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 4.0),
                                            child: getLessonIcon(section
                                                .mLesson![indexLess].lessonType
                                                .toString()),
                                          ),
                                          Expanded(
                                            flex: 1,
                                            child: Text(
                                              lesson.title.toString(),
                                              style: const TextStyle(
                                                fontSize: 14,
                                                color: kGreyLightColor,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      if ((section.mLesson!.length - 1) !=
                                          indexLess)
                                        Divider(
                                          color:
                                              kGreyLightColor.withOpacity(0.3),
                                        ),
                                      if ((section.mLesson!.length - 1) ==
                                          indexLess)
                                        const SizedBox(height: 10),
                                    ],
                                  ),
                                ),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
            const SizedBox(
              height: 10,
            ),
          ],
        ),
      );
    }

    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: _isLoading
            ? const Center(
                child: CircularProgressIndicator(color: kDefaultColor),
              )
            : NestedScrollView(
                controller: _scrollController,
                headerSliverBuilder: (context, value) {
                  return [
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 20),
                            Text(
                              myLoadedCourse.title.toString(),
                              style: const TextStyle(
                                fontWeight: FontWeight.w500,
                                fontSize: 18,
                              ),
                            ),
                            const SizedBox(height: 20),
                            Container(
                              decoration: BoxDecoration(
                                boxShadow: [
                                  BoxShadow(
                                    color: kBackButtonBorderColor
                                        .withOpacity(0.05),
                                    blurRadius: 15,
                                    offset: const Offset(0, 0),
                                  ),
                                ],
                              ),
                              child: Card(
                                elevation: 0.0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 8.0),
                                  child: Column(
                                    children: [
                                      Padding(
                                        padding: const EdgeInsets.symmetric(
                                          vertical: 10,
                                        ),
                                        child: Row(
                                          children: [
                                            Expanded(
                                              flex: 1,
                                              child: Padding(
                                                padding:
                                                    const EdgeInsets.all(10.0),
                                                child: ClipRRect(
                                                  borderRadius:
                                                      BorderRadius.circular(8),
                                                  child:
                                                      FadeInImage.assetNetwork(
                                                    placeholder:
                                                        'assets/images/loading_animated.gif',
                                                    image: myLoadedCourse
                                                        .thumbnail
                                                        .toString(),
                                                    height: 63,
                                                    width: double.infinity,
                                                    fit: BoxFit.cover,
                                                  ),
                                                ),
                                              ),
                                            ),
                                            Expanded(
                                              flex: 2,
                                              child: Padding(
                                                padding: const EdgeInsets.only(
                                                    left: 8.0),
                                                child: RichText(
                                                  textAlign: TextAlign.left,
                                                  text: TextSpan(
                                                    text: myLoadedCourse.title
                                                        .toString(),
                                                    style: const TextStyle(
                                                        fontSize: 15,
                                                        color: Colors.black),
                                                  ),
                                                ),
                                              ),
                                            ),
                                            PopupMenuButton(
                                              onSelected: (value) async {
                                                if (value == 'details') {
                                                  Navigator.of(context).push(
                                                    MaterialPageRoute(
                                                      builder: (context) =>
                                                          CourseDetailScreen(),
                                                      settings: RouteSettings(
                                                        arguments:
                                                            widget.courseId,
                                                      ),
                                                    ),
                                                  );

                                                  print(myLoadedCourse.id);
                                                } else {
                                                  await Share.share(
                                                      myLoadedCourse
                                                          .shareableLink
                                                          .toString());
                                                }
                                              },
                                              icon: const Icon(
                                                Icons.more_vert,
                                                color: kGreyLightColor,
                                              ),
                                              itemBuilder: (_) => [
                                                // const PopupMenuItem(
                                                //   value: 'details',
                                                //   child: Text('Course Details'),
                                                // ),
                                                const PopupMenuItem(
                                                  value: 'share',
                                                  child:
                                                      Text('Share this Course'),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                      LinearPercentIndicator(
                                        lineHeight: 8.0,
                                        backgroundColor:
                                            kGreyLightColor.withOpacity(0.3),
                                        percent:
                                            myLoadedCourse.courseCompletion! /
                                                100,
                                        progressColor: kDefaultColor,
                                        barRadius: const Radius.circular(8),
                                      ),
                                      Padding(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 10, vertical: 15),
                                        child: Row(
                                          children: [
                                            Expanded(
                                              flex: 1,
                                              child: Text(
                                                '${myLoadedCourse.courseCompletion}% Complete',
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w500,
                                                  fontSize: 12,
                                                  color: kGreyLightColor,
                                                ),
                                              ),
                                            ),
                                            Text(
                                              '${myLoadedCourse.totalNumberOfCompletedLessons}/${myLoadedCourse.totalNumberOfLessons}',
                                              style: const TextStyle(
                                                fontWeight: FontWeight.w500,
                                                fontSize: 12,
                                                color: kGreyLightColor,
                                              ),
                                            ),
                                          ],
                                        ),
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
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 25.0, vertical: 10),
                        child: SizedBox(
                          height: 60,
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 5),
                            child: TabBar(
                              controller: _tabController,
                              isScrollable: false,
                              dividerHeight: 0,
                              indicatorColor: kDefaultColor,
                              indicatorSize: TabBarIndicatorSize.tab,
                              indicator: BoxDecoration(
                                  borderRadius: BorderRadius.circular(16),
                                  color: kDefaultColor),
                              // unselectedLabelColor: Colors.black87,
                              labelColor: kWhiteColor,
                              unselectedLabelColor: kDefaultColor,
                              unselectedLabelStyle: const TextStyle(
                                // fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                              tabs: const [
                                Tab(
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.play_lesson,
                                        size: 15,
                                      ),
                                      Text(
                                        'Lessons',
                                        style: TextStyle(
                                          // fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Tab(
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.video_call_outlined),
                                      Text(
                                        'Live Class',
                                        style: TextStyle(
                                          // fontWeight: FontWeight.bold,
                                          fontSize: 14,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    )
                  ];
                },
                body: TabBarView(
                  controller: _tabController,
                  children: [
                    lessonBody(),
                    LiveClassTabWidget(courseId: widget.courseId),
                    // lessonBodyTwo(),
                  ],
                ),
              ),
      ),
    );
  }
}
