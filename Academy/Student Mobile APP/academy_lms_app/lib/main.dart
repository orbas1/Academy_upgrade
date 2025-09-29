import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/course_details.dart';
import 'package:academy_lms_app/screens/login.dart';
import 'package:academy_lms_app/screens/splash.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';
import 'package:logging/logging.dart';
import 'package:provider/provider.dart';

import 'providers/auth.dart';
import 'providers/categories.dart';
import 'providers/community_defaults.dart';
import 'providers/courses.dart';
import 'providers/misc_provider.dart';
import 'providers/my_courses.dart';
import 'providers/search_visibility.dart';
import 'features/search/providers/search_provider.dart';
import 'features/notifications/providers/community_notification_registry.dart';
import 'screens/account_remove_screen.dart';
import 'screens/category_details.dart';
import 'screens/course_detail.dart';
import 'screens/courses_screen.dart';
import 'screens/sub_category.dart';

void main() {
  Logger.root.onRecord.listen((LogRecord rec) {
    debugPrint(
        '${rec.loggerName}>${rec.level.name}: ${rec.time}: ${rec.message}');
  });
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  // This widget is the root of your application.
  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (ctx) => Auth(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => Categories(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => CommunityDefaultsProvider()..hydrateFromSeed(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => Languages(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => CommunityNotificationTemplateRegistry(),
        ),
        ChangeNotifierProxyProvider<Auth, Courses>(
          create: (ctx) => Courses([], [],),
          update: (ctx, auth, prevoiusCourses) => Courses(
            prevoiusCourses == null ? [] : prevoiusCourses.items,
            prevoiusCourses == null ? [] : prevoiusCourses.topItems,
          ),
        ),
        ChangeNotifierProxyProvider<Auth, MyCourses>(
          create: (ctx) => MyCourses([], []),
          update: (ctx, auth, previousMyCourses) => MyCourses(
            previousMyCourses == null ? [] : previousMyCourses.items,
            previousMyCourses == null ? [] : previousMyCourses.sectionItems,
          ),
        ),
        ChangeNotifierProxyProvider<Auth, SearchVisibilityProvider>(
          create: (ctx) => SearchVisibilityProvider(),
          update: (ctx, auth, provider) {
            final visibilityProvider = provider ?? SearchVisibilityProvider();
            visibilityProvider.updateAuthToken(auth.token);
            return visibilityProvider;
          },
        ),
        ChangeNotifierProxyProvider2<Auth, SearchVisibilityProvider, SearchProvider>(
          create: (ctx) => SearchProvider(),
          update: (ctx, auth, visibility, provider) {
            final searchProvider = provider ?? SearchProvider();
            searchProvider.updateContext(
              authToken: auth.token,
              visibilityToken: visibility.token,
            );
            return searchProvider;
          },
        ),
      ],
      child: Consumer<Auth>(
        builder: (ctx, auth, _) => MaterialApp(
          title: 'Academy LMS App',
          theme: ThemeData(
            fontFamily: 'Poppins',
            colorScheme: const ColorScheme.light(primary: kWhiteColor),
            useMaterial3: true,
          ),
          debugShowCheckedModeBanner: false,
          home: const SplashScreen(),
          routes: {
            '/home': (ctx) => const TabsScreen(
                  pageIndex: 0,
                ),
            '/login': (ctx) => const LoginScreen(),
            CoursesScreen.routeName: (ctx) => const CoursesScreen(),
            CategoryDetailsScreen.routeName: (ctx) =>
                const CategoryDetailsScreen(),
            CourseDetailScreen.routeName: (ctx) => const CourseDetailScreen(),
            CourseDetailScreen1.routeName: (ctx) => const CourseDetailScreen1(),
            SubCategoryScreen.routeName: (ctx) => const SubCategoryScreen(),
            AccountRemoveScreen.routeName: (ctx) => const AccountRemoveScreen(),
          },
        ),
      ),
    );
  }
}
