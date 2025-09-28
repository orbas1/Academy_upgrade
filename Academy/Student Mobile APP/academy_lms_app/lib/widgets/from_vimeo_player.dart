import 'dart:async';
import 'dart:convert';

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/providers/my_courses.dart';
import 'package:academy_lms_app/providers/shared_pref_helper.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:vimeo_embed_player/vimeo_embed_player.dart';

class FromVimeoPlayer extends StatefulWidget {
  final int courseId;
  final int? lessonId;
  final String vimeoVideoId;

  const FromVimeoPlayer(
      {super.key,
      required this.vimeoVideoId,
      required this.courseId,
      this.lessonId});

  @override
  State<FromVimeoPlayer> createState() => _FromVimeoPlayerState();
}

class _FromVimeoPlayerState extends State<FromVimeoPlayer> {
  Timer? timer;
  bool isPlaying = false;
  int currentVideoPosition = 0;

  @override
  void initState() {
    super.initState();

    if (widget.lessonId != null) {
      timer = Timer.periodic(
          const Duration(seconds: 5), (Timer t) => updateWatchHistory());
    }
  }

  Future<void> updateWatchHistory() async {
    if (isPlaying) {
      var token = await SharedPreferenceHelper().getAuthToken();
      dynamic url;

      if (token != null && token.isNotEmpty) {
        url = "$baseUrl/api/update_watch_history/$token";
        try {
          final response = await http.post(
            Uri.parse(url),
            body: {
              'course_id': widget.courseId.toString(),
              'lesson_id': widget.lessonId.toString(),
              'current_duration': currentVideoPosition.toString(),
            },
          );

          final responseData = json.decode(response.body);
          print("Arif response here ::: $responseData");
          if (responseData != null) {
            var isCompleted = responseData['is_completed'];
            print("Arif output here ::: $isCompleted");
            if (isCompleted == 1) {
              Provider.of<MyCourses>(context, listen: false)
                  .updateDripContendLesson(
                      widget.courseId,
                      responseData['course_progress'],
                      responseData['number_of_completed_lessons']);
              print(
                  "Arif output here ::: $responseData['number_of_completed_lessons']");
            }
          }
        } catch (error) {
          rethrow;
        }
      }
    }
  }

  void onPlay() {
    setState(() {
      isPlaying = true;
      // Start a timer to simulate video position increment
      Timer.periodic(const Duration(seconds: 1), (timer) {
        if (!isPlaying) {
          timer.cancel(); // Stop updating if video is not playing
        } else {
          currentVideoPosition++; // Increment position by 1 second
        }
      });
    });
  }

  void onPause() {
    setState(() {
      isPlaying = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(),
      body: Center(
        child: GestureDetector(
          onTap: () {
            // Toggle between play and pause
            isPlaying ? onPause() : onPlay();
          },
          child: AspectRatio(
            aspectRatio: 16.0 / 9.0,
            child: VimeoEmbedPlayer(
              vimeoId: widget.vimeoVideoId,
              autoPlay: true,
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    timer?.cancel();
    super.dispose();
  }
}
