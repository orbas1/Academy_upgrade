// ignore_for_file: use_build_context_synchronously
import 'dart:convert';

import 'package:academy_lms_app/models/course_detail.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'package:html_unescape/html_unescape.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../services/security/auth_session_manager.dart';
// import 'package:url_launcher/url_launcher.dart';

import '../constants.dart';
import '../providers/courses.dart';
import '../widgets/appbar_one.dart';
import '../widgets/common_functions.dart';
import '../widgets/from_network.dart';
import '../widgets/lesson_list_item.dart';
import '../widgets/tab_view_details.dart';
import '../widgets/util.dart';
import 'filter_screen.dart';

class CourseDetailScreen1 extends StatefulWidget {
  static const routeName = '/course-details1';
  final String? courseId;
  const CourseDetailScreen1({super.key, this.courseId});

  @override
  State<CourseDetailScreen1> createState() => _CourseDetailScreen1State();
}

class _CourseDetailScreen1State extends State<CourseDetailScreen1>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int? selected;
  dynamic token;
  bool _isInit = true;
  bool _isAuth = false;
  bool _isLoading = false;
  bool isLoading = false;
  // dynamic courseId;
  CourseDetails? loadedCourse;
  var msg = 'Removed from cart';
  var msg1 = 'please tap again to Buy Now';

  getEnroll(String course_id) async {
    setState(() {
      isLoading = true;
    });
    String url = "$baseUrl/api/free_course_enroll/$course_id";
    var navigator = Navigator.of(context);
    final sessionManager = AuthSessionManager.instance;
    final accessToken = await sessionManager.requireAccessToken();
    setState(() {
      token = accessToken;
    });
    var response = await http.get(Uri.parse(url), headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $accessToken',
    });
    print(url);
    print(token);

    final data = jsonDecode(response.body);
    // print(data['message']);
    // print(response.body);
    if (response.statusCode == 200) {
      navigator.pushReplacement(
        MaterialPageRoute(
            builder: (context) => TabsScreen(
                  pageIndex: 1,
                )),
      );
      setState(() {
        isLoading = false;
      });
    } else {
      Fluttertoast.showToast(msg: data['message']);
    }
    setState(() {
      isLoading = false;
    });
  }

  @override
  void initState() {
    _tabController = TabController(length: 3, vsync: this);
    super.initState();
  }

  @override
  void didChangeDependencies() {
    if (_isInit) {
      _bootstrapCourseDetails();
      _isInit = false;
    }
    super.didChangeDependencies();
  }

  Future<void> _bootstrapCourseDetails() async {
    final sessionManager = AuthSessionManager.instance;
    final resolvedToken = await sessionManager.getValidAccessToken();

    if (!mounted) {
      return;
    }

    setState(() {
      token = resolvedToken;
      _isLoading = true;
      _isAuth = resolvedToken != null && resolvedToken.isNotEmpty;
    });

    try {
      await Provider.of<Courses>(context, listen: false)
          .fetchCourseDetails(widget.courseId);
    } finally {
      if (!mounted) {
        return;
      }
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    // final courseId = ModalRoute.of(context)!.settings.arguments as int;
    // final loadedCourse = Provider.of<Courses>(
    //   context,
    //   listen: false,
    // ).findById(courseId);
    // final loadedCourse = Provider.of<Courses>(
    //   context,
    //   listen: false,
    // ).getCourseDetail;

    // customNavBar() {
    //   return Padding(
    //     padding: const EdgeInsets.symmetric(horizontal: 20.0),
    //     child: SizedBox(
    //       height: 65,
    //       child: Row(
    //         mainAxisAlignment: MainAxisAlignment.spaceAround,
    //         children: [
    //           _isLoading
    //               ? const Center(
    //                   child: CircularProgressIndicator(color: kDefaultColor),
    //                 )
    //               : Column(
    //                   mainAxisSize: MainAxisSize.min,
    //                   children: [
    //                     // Text(loadedCourse.isPurchased.toString()),
    //                     IconButton(
    //                         icon: SvgPicture.asset(
    //                           'assets/icons/account.svg',
    //                           colorFilter: const ColorFilter.mode(
    //                               kGreyLightColor, BlendMode.srcIn),
    //                         ),
    //                         onPressed: () {
    //                           // Handle account icon tap
    //                           // You can navigate to the account page or show a user menu here
    //                           Navigator.push(
    //                               context,
    //                               MaterialPageRoute(
    //                                   builder: (context) => const TabsScreen(
    //                                         pageIndex: 3,
    //                                       )));
    //                         },
    //                         visualDensity: const VisualDensity(
    //                             horizontal: -4, vertical: -4)),
    //                     const Text(
    //                       'Account',
    //                       style: TextStyle(
    //                         fontSize: 12,
    //                         fontWeight: FontWeight.w500,
    //                         color: kGreyLightColor,
    //                       ),
    //                     ),
    //                   ],
    //                 ),
    //           const Padding(
    //             padding: EdgeInsets.symmetric(horizontal: 15.0, vertical: 15),
    //             child: VerticalDivider(
    //               thickness: 1.0, // Adjust the thickness of the divider
    //               color: kGreyLightColor, // Adjust the color of the divider
    //             ),
    //           ),
    //           loadedCourse.isPurchased!
    //               ? SizedBox()
    //               : loadedCourse.isPaid == 1
    //                   ? Padding(
    //                       padding: const EdgeInsets.only(right: 10.0),
    //                       child: MaterialButton(
    //                         elevation: 0,
    //                         padding: const EdgeInsets.symmetric(horizontal: 10),
    //                         onPressed: () async {
    //                           final prefs =
    //                               await SharedPreferences.getInstance();
    //                           final authToken =
    //                               (prefs.getString('access_token') ?? '');
    //                           if (authToken.isNotEmpty) {
    //                             if (loadedCourse.isPaid == 1) {
    //                               if (msg1 == 'please tap again to Buy Now') {
    //                                 setState(() {
    //                                   msg1 = 'Added to cart';
    //                                 });

    //                                 final prefs =
    //                                     await SharedPreferences.getInstance();
    //                                 final emailPre = prefs.getString('email');
    //                                 final passwordPre =
    //                                     prefs.getString('password');
    //                                 var email = emailPre;
    //                                 var password = passwordPre;
    //                                 // print(email);
    //                                 // print(password);
    //                                 // var email = "student@example.com";
    //                                 // var password = "12345678";
    //                                 DateTime currentDateTime = DateTime.now();
    //                                 int currentTimestamp = (currentDateTime
    //                                             .millisecondsSinceEpoch /
    //                                         1000)
    //                                     .floor();

    //                                 String authToken =
    //                                     'Basic ${base64Encode(utf8.encode('$email:$password:$currentTimestamp'))}';
    //                                 // print(authToken);
    //                                 final url =
    //                                     '$baseUrl/payment/web_redirect_to_pay_fee?auth=$authToken&unique_id=academylaravelbycreativeitem';
    //                                 // print(url);
    //                                 // _launchURL(url);

    //                                 if (await canLaunchUrl(Uri.parse(url))) {
    //                                   await launchUrl(
    //                                     Uri.parse(url),
    //                                     mode: LaunchMode.externalApplication,
    //                                   );
    //                                 } else {
    //                                   throw 'Could not launch $url';
    //                                 }
    //                               } else if (msg1 == 'Added to cart') {
    //                                 setState(() {
    //                                   msg1 = 'please tap again to Buy Now';
    //                                 });
    //                               }
    //                               CommonFunctions.showSuccessToast(msg1);
    //                               Provider.of<Courses>(context, listen: false)
    //                                   .toggleCart(loadedCourse.id!, false);
    //                             }

    //                             // CommonFunctions.showSuccessToast('Failed to connect');
    //                           } else {
    //                             CommonFunctions.showWarningToast(
    //                                 'Please login first');
    //                           }
    //                         },
    //                         color: kDefaultColor,
    //                         height: 45,
    //                         minWidth: 111,
    //                         textColor: Colors.white,
    //                         shape: RoundedRectangleBorder(
    //                           borderRadius: BorderRadius.circular(13.0),
    //                           side: const BorderSide(color: kDefaultColor),
    //                         ),
    //                         child: const Text(
    //                           'Buy Now',
    //                           style: TextStyle(
    //                             fontWeight: FontWeight.w500,
    //                             fontSize: 13,
    //                           ),
    //                         ),
    //                       ),
    //                     )
    //                   : SizedBox(
    //                       width: 111,
    //                     ),
    //           loadedCourse.isPurchased!
    //               ? Padding(
    //                   padding: const EdgeInsets.only(right: 10.0),
    //                   child: MaterialButton(
    //                     elevation: 0,
    //                     padding: const EdgeInsets.symmetric(horizontal: 10),
    //                     onPressed: () async {
    //                       // await getEnroll(loadedCourse.id.toString());
    //                       final prefs = await SharedPreferences.getInstance();
    //                       final authToken =
    //                           (prefs.getString('access_token') ?? '');
    //                       if (authToken.isNotEmpty) {
    //                         Navigator.of(context).pushReplacement(
    //                           MaterialPageRoute(
    //                               builder: (context) => TabsScreen(
    //                                     pageIndex: 1,
    //                                   )),
    //                         );
    //                       } else {
    //                         CommonFunctions.showWarningToast(
    //                             'Please login first');
    //                       }
    //                     },
    //                     color: kGreenPurchaseColor,
    //                     height: 45,
    //                     minWidth: 111,
    //                     textColor: Colors.white,
    //                     shape: RoundedRectangleBorder(
    //                       borderRadius: BorderRadius.circular(13.0),
    //                       side: const BorderSide(color: kGreenPurchaseColor),
    //                     ),
    //                     child: const Text(
    //                       'Purchased',
    //                       style: TextStyle(
    //                         fontWeight: FontWeight.w500,
    //                         fontSize: 13,
    //                       ),
    //                     ),
    //                   ),
    //                 )
    //               : loadedCourse.isPaid == 1
    //                   ? MaterialButton(
    //                       elevation: 0,
    //                       padding: const EdgeInsets.symmetric(horizontal: 10),
    //                       onPressed: () async {
    //                         final prefs = await SharedPreferences.getInstance();
    //                         final authToken =
    //                             (prefs.getString('access_token') ?? '');
    //                         if (authToken.isNotEmpty) {
    //                           if (loadedCourse.isPaid == 1) {
    //                             if (msg == 'Removed from cart') {
    //                               setState(() {
    //                                 msg = 'Added to cart';
    //                               });
    //                             } else if (msg == 'Added to cart') {
    //                               setState(() {
    //                                 msg = 'Removed from cart';
    //                               });
    //                             }
    //                             CommonFunctions.showSuccessToast(msg);
    //                             Provider.of<Courses>(context, listen: false)
    //                                 .toggleCart(loadedCourse.id!, false);
    //                           } else {
    //                             CommonFunctions.showWarningToast(
    //                                 "It's a free course! Click on Buy Now");
    //                           }
    //                         } else {
    //                           CommonFunctions.showSuccessToast(
    //                               'Please login first');
    //                         }
    //                       },
    //                       color: kWhiteColor,
    //                       height: 45,
    //                       minWidth: 111,
    //                       textColor: const Color.fromARGB(255, 102, 76, 76),
    //                       shape: RoundedRectangleBorder(
    //                         borderRadius: BorderRadius.circular(13.0),
    //                         side: const BorderSide(color: kDefaultColor),
    //                       ),
    //                       child: const Text(
    //                         'Add to Cart',
    //                         style: TextStyle(
    //                             fontWeight: FontWeight.w500,
    //                             fontSize: 13,
    //                             color: kDefaultColor),
    //                       ),
    //                     )
    //                   : Padding(
    //                       padding: const EdgeInsets.only(right: 10.0),
    //                       child: MaterialButton(
    //                         elevation: 0,
    //                         padding: const EdgeInsets.symmetric(horizontal: 10),
    //                         onPressed: () async {
    //                           // await getEnroll(loadedCourse.id.toString());
    //                           final prefs =
    //                               await SharedPreferences.getInstance();
    //                           final authToken =
    //                               (prefs.getString('access_token') ?? '');
    //                           if (authToken.isNotEmpty) {
    //                             if (loadedCourse.isPaid == 0) {
    //                               await getEnroll(loadedCourse.id.toString());
    //                               // print(loadedCourse.id.toString());
    //                               CommonFunctions.showSuccessToast(
    //                                   'Course Succesfully Enrolled');
    //                             }

    //                             // CommonFunctions.showSuccessToast(
    //                             //     'Failed to connect');
    //                           } else {
    //                             CommonFunctions.showWarningToast(
    //                                 'Please login first');
    //                           }
    //                         },
    //                         color: kDefaultColor,
    //                         height: 45,
    //                         minWidth: 111,
    //                         textColor: Colors.white,
    //                         shape: RoundedRectangleBorder(
    //                           borderRadius: BorderRadius.circular(13.0),
    //                           side: const BorderSide(color: kDefaultColor),
    //                         ),
    //                         child: Text(
    //                           'Enroll Now',
    //                           style: TextStyle(
    //                             fontWeight: FontWeight.w500,
    //                             fontSize: 13,
    //                           ),
    //                         ),
    //                       ),
    //                     )
    //         ],
    //       ),
    //     ),
    //   );
    // }

    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: _isLoading
            ? const Center(
                child: CircularProgressIndicator(color: kDefaultColor),
              )
            : Consumer<Courses>(builder: (context, courses, child) {
                final loadedCourse = courses.courseDetails;
                return SingleChildScrollView(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Text(loadedCourse.isPurchased.toString()),
                        Stack(
                          fit: StackFit.loose,
                          alignment: Alignment.center,
                          clipBehavior: Clip.none,
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(30),
                              child: Container(
                                alignment: Alignment.center,
                                height:
                                    MediaQuery.of(context).size.height * .31,
                                decoration: BoxDecoration(
                                    image: DecorationImage(
                                  fit: BoxFit.cover,
                                  colorFilter: ColorFilter.mode(
                                      Colors.black.withOpacity(0.6),
                                      BlendMode.dstATop),
                                  image: NetworkImage(
                                    loadedCourse.thumbnail.toString(),
                                  ),
                                )),
                              ),
                            ),
                            ClipOval(
                              child: InkWell(
                                onTap: () {
                                  Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                        builder: (context) =>
                                            PlayVideoFromNetwork(
                                                courseId: loadedCourse.id!,
                                                videoUrl:
                                                    loadedCourse.preview!)),
                                  );
                                },
                                child: Container(
                                  width: 50,
                                  height: 50,
                                  decoration: const BoxDecoration(
                                    color: Colors.white,
                                    boxShadow: [kDefaultShadow],
                                  ),
                                  child: Padding(
                                    padding: const EdgeInsets.all(5.0),
                                    child: Image.asset(
                                      'assets/images/play.png',
                                      fit: BoxFit.contain,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            Positioned(
                              top: 15,
                              right: 15,
                              child: SizedBox(
                                height: 45,
                                width: 45,
                                child: FittedBox(
                                  child: FloatingActionButton(
                                    onPressed: () {
                                      if (_isAuth) {
                                        var msg = loadedCourse.isWishlisted;
                                        showDialog(
                                          context: context,
                                          builder: (BuildContext context) =>
                                              buildPopupDialogWishList(
                                                  context,
                                                  loadedCourse.isWishlisted,
                                                  loadedCourse.id,
                                                  msg),
                                        );
                                      } else {
                                        CommonFunctions.showSuccessToast(
                                            'Please login first');
                                      }
                                    },
                                    tooltip: 'Wishlist',
                                    backgroundColor: loadedCourse.isWishlisted!
                                        ? Colors.white
                                        : kGreyLightColor.withOpacity(0.3),
                                    shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(57)),
                                    child: Icon(
                                      loadedCourse.isWishlisted!
                                          ? Icons.favorite
                                          : Icons.favorite,
                                      size: 30,
                                      color: loadedCourse.isWishlisted!
                                          ? kDefaultColor
                                          : Colors.white,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        Padding(
                          padding: const EdgeInsets.only(
                              top: 25.0, left: 5, right: 5),
                          child: Row(
                            children: [
                              Expanded(
                                flex: 1,
                                child: Text(
                                  loadedCourse.title.toString(),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                              InkWell(
                                onTap: () async {
                                  await Share.share(
                                      loadedCourse.shareableLink.toString());
                                },
                                child: SvgPicture.asset(
                                  'assets/icons/share.svg',
                                  height: 24,
                                  width: 16,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.all(5.0),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.start,
                            children: <Widget>[
                              const Padding(
                                padding: EdgeInsets.only(
                                  right: 10,
                                ),
                                child: Icon(
                                  Icons.star,
                                  color: kStarColor,
                                  size: 18,
                                ),
                              ),
                              Padding(
                                padding: EdgeInsets.only(right: 5),
                                child: Text(
                                  loadedCourse.averageRating.toString(),
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w400,
                                    color: kGreyLightColor,
                                  ),
                                ),
                              ),
                              Text(
                                '(${loadedCourse.total_reviews.toString()} Reviews)',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w400,
                                  color: kGreyLightColor,
                                ),
                              ),
                              const Spacer(),
                              Text(
                                loadedCourse.price.toString(),
                                style: const TextStyle(
                                    fontSize: 28, fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        ),
                        SingleChildScrollView(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: <Widget>[
                              Container(
                                decoration: BoxDecoration(
                                  boxShadow: [
                                    BoxShadow(
                                      color: kBackButtonBorderColor
                                          .withOpacity(0.07),
                                      blurRadius: 15,
                                      offset: const Offset(0, 0),
                                    ),
                                  ],
                                ),
                                child: Card(
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(16),
                                  ),
                                  child: Column(
                                    children: [
                                      SizedBox(
                                        width: double.infinity,
                                        child: TabBar(
                                          controller: _tabController,
                                          indicatorSize:
                                              TabBarIndicatorSize.tab,
                                          indicator: BoxDecoration(
                                              borderRadius:
                                                  BorderRadius.circular(13),
                                              color: kDefaultColor),
                                          // unselectedLabelColor: kTextColor,
                                          unselectedLabelStyle: const TextStyle(
                                            fontWeight: FontWeight.w500,
                                            fontSize: 13,
                                          ),
                                          labelStyle: const TextStyle(
                                            fontWeight: FontWeight.w500,
                                            fontSize: 13,
                                            color: kWhiteColor,
                                          ),
                                          padding: const EdgeInsets.all(10),
                                          dividerHeight: 0,
                                          // labelColor: Colors.white,
                                          tabs: const <Widget>[
                                            Tab(
                                              child: Text(
                                                "Includes",
                                                style: TextStyle(
                                                  fontWeight: FontWeight.w500,
                                                  fontSize: 14,
                                                ),
                                              ),
                                            ),
                                            Tab(
                                              child: Align(
                                                alignment: Alignment.center,
                                                child: Text(
                                                  "Outcomes",
                                                  style: TextStyle(
                                                    fontWeight: FontWeight.w500,
                                                    fontSize: 14,
                                                  ),
                                                ),
                                              ),
                                            ),
                                            Tab(
                                              child: Align(
                                                alignment: Alignment.center,
                                                child: Text(
                                                  "Required",
                                                  style: TextStyle(
                                                    fontWeight: FontWeight.w500,
                                                    fontSize: 14,
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Container(
                                        width: double.infinity,
                                        height: 215,
                                        padding: const EdgeInsets.only(
                                            right: 10,
                                            left: 10,
                                            top: 0,
                                            bottom: 10),
                                        child: TabBarView(
                                          controller: _tabController,
                                          children: [
                                            TabViewDetails(
                                              titleText: 'What is Included',
                                              listText: loadedCourse.includes,
                                            ),
                                            TabViewDetails(
                                              titleText: 'What you will learn',
                                              listText: loadedCourse.includes,
                                            ),
                                            TabViewDetails(
                                              titleText: 'Course Requirements',
                                              listText: loadedCourse.includes,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              const Padding(
                                padding: EdgeInsets.symmetric(
                                    vertical: 20, horizontal: 10),
                                child: Text(
                                  'Course curriculum',
                                  style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w500),
                                ),
                              ),
                              ListView.builder(
                                key: Key('builder ${selected.toString()}'),
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                itemCount: loadedCourse.sections!.length,
                                itemBuilder: (ctx, index) {
                                  final section = loadedCourse.sections![index];
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 5.0),
                                    child: Container(
                                      decoration: BoxDecoration(
                                        boxShadow: [
                                          BoxShadow(
                                            color: kBackButtonBorderColor
                                                .withOpacity(0.05),
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
                                                ? Icons
                                                    .keyboard_arrow_up_rounded
                                                : Icons
                                                    .keyboard_arrow_down_rounded,
                                            size: 35,
                                          ),
                                          shape: RoundedRectangleBorder(
                                            borderRadius:
                                                BorderRadiusDirectional
                                                    .circular(16),
                                            side: const BorderSide(
                                                color: Colors.white),
                                          ),
                                          title: Padding(
                                            padding: const EdgeInsets.symmetric(
                                                vertical: 5.0),
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Align(
                                                  alignment:
                                                      Alignment.centerLeft,
                                                  child: Padding(
                                                    padding: const EdgeInsets
                                                        .symmetric(
                                                      vertical: 5.0,
                                                    ),
                                                    child: Text(
                                                      '${index + 1}. ${HtmlUnescape().convert(section.title.toString())}',
                                                      style: const TextStyle(
                                                        fontSize: 16,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                Padding(
                                                  padding: const EdgeInsets
                                                      .symmetric(vertical: 5.0),
                                                  child: Row(
                                                    children: [
                                                      Expanded(
                                                        flex: 1,
                                                        child: Container(
                                                          decoration:
                                                              BoxDecoration(
                                                            color: kTimeBackColor
                                                                .withOpacity(
                                                                    0.12),
                                                            borderRadius:
                                                                BorderRadius
                                                                    .circular(
                                                                        5),
                                                          ),
                                                          padding:
                                                              const EdgeInsets
                                                                  .symmetric(
                                                            vertical: 5.0,
                                                          ),
                                                          child: Align(
                                                            alignment: Alignment
                                                                .center,
                                                            child: Text(
                                                              section
                                                                  .totalDuration
                                                                  .toString(),
                                                              style:
                                                                  const TextStyle(
                                                                fontSize: 10,
                                                                fontWeight:
                                                                    FontWeight
                                                                        .w400,
                                                                color:
                                                                    kTimeColor,
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
                                                          decoration:
                                                              BoxDecoration(
                                                            color:
                                                                kLessonBackColor
                                                                    .withOpacity(
                                                                        0.12),
                                                            borderRadius:
                                                                BorderRadius
                                                                    .circular(
                                                                        5),
                                                          ),
                                                          padding:
                                                              const EdgeInsets
                                                                  .symmetric(
                                                            vertical: 5.0,
                                                          ),
                                                          child: Align(
                                                            alignment: Alignment
                                                                .center,
                                                            child: Text(
                                                              '${section.mLesson!.length} Lessons',
                                                              style:
                                                                  const TextStyle(
                                                                fontSize: 10,
                                                                fontWeight:
                                                                    FontWeight
                                                                        .w400,
                                                                color:
                                                                    kLessonColor,
                                                              ),
                                                            ),
                                                          ),
                                                        ),
                                                      ),
                                                      const Expanded(
                                                          flex: 1,
                                                          child: Text("")),
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          children: [
                                            ListView.builder(
                                              shrinkWrap: true,
                                              physics:
                                                  const NeverScrollableScrollPhysics(),
                                              itemCount:
                                                  section.mLesson!.length,
                                              itemBuilder: (ctx, index) {
                                                return Padding(
                                                  padding: const EdgeInsets
                                                      .symmetric(
                                                      horizontal: 15.0),
                                                  child: Column(
                                                    children: [
                                                      LessonListItem(
                                                        lesson: section
                                                            .mLesson![index],
                                                        courseId:
                                                            loadedCourse.id!,
                                                      ),
                                                      if ((section.mLesson!
                                                                  .length -
                                                              1) !=
                                                          index)
                                                        Divider(
                                                          color: kGreyLightColor
                                                              .withOpacity(0.3),
                                                        ),
                                                      if ((section.mLesson!
                                                                  .length -
                                                              1) ==
                                                          index)
                                                        const SizedBox(
                                                            height: 10),
                                                    ],
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
                        ),
                      ],
                    ),
                  ),
                );
              }),
      ),
      floatingActionButton: Container(
        margin: const EdgeInsets.only(bottom: 15.0),
        padding: const EdgeInsets.only(right: 10.0, bottom: 18),
        child: FloatingActionButton(
          onPressed: () {
            Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const FilterScreen(),
                ));
          },
          backgroundColor: kWhiteColor,
          shape: RoundedRectangleBorder(
              side: const BorderSide(width: 1, color: kDefaultColor),
              borderRadius: BorderRadius.circular(100)),
          child: SvgPicture.asset(
            'assets/icons/filter.svg',
            colorFilter: const ColorFilter.mode(
              kBlackColor,
              BlendMode.srcIn,
            ),
          ),
        ),
      ),
      // bottomNavigationBar: customNavBar(),
    );
  }
}
