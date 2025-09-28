// ignore_for_file: prefer_const_constructors, unused_element

import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../constants.dart';
import '../models/live_class_model.dart';

class LiveClassTabWidget extends StatefulWidget {
  final int courseId;
  const LiveClassTabWidget({super.key, required this.courseId});

  @override
  // ignore: library_private_types_in_public_api
  _LiveClassTabWidgetState createState() => _LiveClassTabWidgetState();
}

class _LiveClassTabWidgetState extends State<LiveClassTabWidget> {
  dynamic token;

  Future<LiveClassModel>? futureLiveClassModel;

  Future<LiveClassModel> fetchLiveClassModel() async {
    final prefs = await SharedPreferences.getInstance();
    final authToken = (prefs.getString('access_token') ?? '');
    var url = '$baseUrl/api/zoom/meetings?course_id=${widget.courseId}';
    try {
      final response = await http.get(Uri.parse(url), headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $authToken',
      });

      return LiveClassModel.fromJson(json.decode(response.body));
    } catch (error) {
      rethrow;
    }
  }

  @override
  void initState() {
    super.initState();
    futureLiveClassModel = fetchLiveClassModel();
  }

  @override
  Widget build(BuildContext context) {
    //    dynamic joinUrl ="";
    // final Uri url = Uri.parse(joinUrl);

    // Future<void> onLaunchURL(String joinUrl) async {
    //   Uri url = Uri.parse(joinUrl);
    //   if (!await launchUrl(url)) {
    //     throw Exception("Error in this link");
    //   }
    // }

    return FutureBuilder<LiveClassModel>(
      future: futureLiveClassModel,
      builder: (ctx, dataSnapshot) {
        if (dataSnapshot.connectionState == ConnectionState.waiting) {
          return SizedBox(
            height: MediaQuery.of(context).size.height * .50,
            child: const Center(
              child: CircularProgressIndicator(),
            ),
          );
        } else {
          if (dataSnapshot.error != null) {
            //error
            return Column(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(
                      vertical: 20.0, horizontal: 15),
                  child: Container(
                    width: double.infinity,
                    color: kTextLowBlackColor,
                    child: const Padding(
                      padding:
                          EdgeInsets.symmetric(horizontal: 8.0, vertical: 8.0),
                      child: Text(
                        'No live class is scheduled to this course yet. Please come back later.',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 15,
                          height: 1.5,
                          wordSpacing: 1,
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            );
          } else {
            return ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: dataSnapshot.data!.liveClasses!.length,
              itemBuilder: (ctx, index) {
                final liveClass = dataSnapshot.data!.liveClasses![index];
                return Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 20.0, vertical: 5),
                  child: Card(
                    elevation: 0.3,
                    child: Column(
                      children: [
                        const Padding(
                          padding: EdgeInsets.only(top: 25.0, bottom: 13),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.event_available,
                                color: Colors.black,
                              ),
                              Padding(
                                padding: EdgeInsets.only(left: 6.0),
                                child: Text(
                                  'Zoom live class schedule',
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w400,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: EdgeInsets.only(left: 6.0),
                          child: Text(
                            dataSnapshot.data!.zoomSdk.toString(),
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.w400,
                            ),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.only(bottom: 13),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(
                                Icons.access_time,
                                color: Colors.black,
                              ),
                              Padding(
                                padding: const EdgeInsets.only(left: 6.0),
                                child: Text(
                                  liveClass.classDateAndTime.toString(),
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w400,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 15.0),
                          child: Container(
                            color: kGreyLightColor.withOpacity(.1),
                            width: double.infinity,
                            // color: kNoteColor,
                            child: const Padding(
                              padding: EdgeInsets.symmetric(vertical: 15.0),
                              child: Text(
                                'Everyone must join',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 15,
                                  height: 1.5,
                                  wordSpacing: 1,
                                ),
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 10),
                        ElevatedButton.icon(
                          // onPressed: () {
                          //   // Navigator.push(
                          //   //   context,
                          //   //   MaterialPageRoute(
                          //   //       builder: (context) =>
                          //   //           const MeetingWidget()));
                          //   dataSnapshot.data!.zoomSdk == "active"
                          //       ? Navigator.push(
                          //           context,
                          //           MaterialPageRoute(
                          //               builder: (context) => JoinMeetingScreen(
                          //                   meetingId:
                          //                       liveClass.meetingId.toString(),
                          //                   meetingPass: liveClass
                          //                       .meetingPassword
                          //                       .toString(),
                          //                   meetingClientKey: dataSnapshot
                          //                       .data!.zoomSdkClientId
                          //                       .toString(),
                          //                   meetingClientSecret: dataSnapshot
                          //                       .data!.zoomSdkClientSecret
                          //                       .toString())),
                          //         )
                          //       : launchUrl(Uri.parse(liveClass.joinUrl));
                          // },
                          onPressed: () {
                            // if (dataSnapshot.data!.zoomSdk == "active") {
                            //   Navigator.push(
                            //     context,
                            //     MaterialPageRoute(
                            //       builder: (context) => JoinMeetingScreen(
                            //         meetingId: liveClass.meetingId.toString(),
                            //         meetingPass:
                            //             liveClass.meetingPassword.toString(),
                            //         meetingClientKey: dataSnapshot
                            //             .data!.zoomSdkClientId
                            //             .toString(),
                            //         meetingClientSecret: dataSnapshot
                            //             .data!.zoomSdkClientSecret
                            //             .toString(),
                            //       ),
                            //     ),
                            //   );
                            // } else {
                            // Check if joinUrl is not null before parsing
                            if (liveClass.joinUrl != null) {
                              final joinUri = Uri.parse(liveClass.joinUrl!);
                              launchUrl(joinUri);
                            } else {
                              // Handle the case where joinUrl is null
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                    content: Text('Join URL is not available')),
                              );
                            }
                            // }
                          },

                          style: ElevatedButton.styleFrom(
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16.0),
                            ),
                            backgroundColor: kDefaultColor,
                          ),
                          icon: const Icon(
                            Icons.videocam_rounded,
                            color: kWhiteColor,
                          ),
                          label: const Padding(
                            padding: EdgeInsets.symmetric(vertical: 13),
                            child: Text(
                              'Join live video class',
                              style: TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.w400,
                                  color: kWhiteColor),
                            ),
                          ),
                        ),
                        const SizedBox(height: 15),
                      ],
                    ),
                  ),
                );
              },
            );
          }
        }
      },
    );
  }
}
