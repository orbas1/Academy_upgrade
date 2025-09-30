import 'package:academy_lms_app/screens/course_details.dart';
import 'package:academy_lms_app/screens/login.dart';
import 'package:academy_lms_app/screens/splash.dart';
import 'package:academy_lms_app/screens/tab_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:logging/logging.dart';
import 'package:provider/provider.dart';

import 'config/design_tokens.dart';
import 'config/app_configuration.dart';
import 'providers/auth.dart';
import 'providers/categories.dart';
import 'providers/app_locale_notifier.dart';
import 'providers/community_defaults.dart';
import 'providers/courses.dart';
import 'providers/misc_provider.dart';
import 'providers/my_courses.dart';
import 'providers/notification_preferences.dart';
import 'providers/search_results.dart';
import 'providers/search_visibility.dart';
import 'screens/account_remove_screen.dart';
import 'screens/category_details.dart';
import 'screens/course_detail.dart';
import 'screens/courses_screen.dart';
import 'screens/sub_category.dart';
import 'services/messaging/deep_link_handler.dart';
import 'services/messaging/push_notification_router.dart';
import 'services/telemetry/telemetry_service.dart';
import 'features/communities/data/community_cache.dart';
import 'features/communities/di/providers.dart';
import 'services/analytics/mobile_analytics_service.dart';
import 'l10n/app_localizations.dart';

final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();
final DeepLinkHandler deepLinkHandler = DeepLinkHandler(navigatorKey: appNavigatorKey);
final PushNotificationRouter pushNotificationRouter =
    PushNotificationRouter(deepLinkHandler: deepLinkHandler);
final CommunityCache communityCache = CommunityCache();

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  Logger.root.onRecord.listen((LogRecord rec) {
    debugPrint(
        '${rec.loggerName}>${rec.level.name}: ${rec.time}: ${rec.message}');
  });
  final configuration = AppConfiguration.instance;
  final telemetry = TelemetryService.instance;
  telemetry.environment = configuration.environment;

  await MobileAnalyticsService.instance.ensureInitialised();

  FlutterError.onError = (FlutterErrorDetails details) {
    FlutterError.presentError(details);
    telemetry.recordError(details.exception, details.stack ?? StackTrace.empty);
  };

  await telemetry.ensureInitialized(
    sentryDsn: configuration.sentryDsn,
    runner: () {
      runApp(const MyApp());
    },
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  // This widget is the root of your application.
  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<DeepLinkHandler>.value(value: deepLinkHandler),
        Provider<PushNotificationRouter>.value(value: pushNotificationRouter),
        ChangeNotifierProvider(
          create: (ctx) => Auth(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => Categories(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => NotificationPreferencesProvider(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => CommunityDefaultsProvider()..hydrateFromSeed(),
        ),
        ...communityProviders(cache: communityCache),
        ChangeNotifierProvider(
          create: (ctx) => Languages(),
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
        ChangeNotifierProvider(
          create: (ctx) => SearchResultsProvider(),
        ),
        ChangeNotifierProvider(
          create: (ctx) => AppLocaleNotifier(),
        ),
      ],
      child: Consumer2<Auth, AppLocaleNotifier>(
        builder: (ctx, auth, localeNotifier, _) => MaterialApp(
          onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
          locale: localeNotifier.locale,
          supportedLocales: AppLocalizations.supportedLocales,
          localizationsDelegates: const [
            AppLocalizationsDelegate(),
            GlobalMaterialLocalizations.delegate,
            GlobalWidgetsLocalizations.delegate,
            GlobalCupertinoLocalizations.delegate,
          ],
          localeResolutionCallback: (locale, supportedLocales) =>
              AppLocalizations.resolutionCallback(locale, supportedLocales),
          theme: buildAppTheme(),
          debugShowCheckedModeBanner: false,
          navigatorKey: appNavigatorKey,
          navigatorObservers: TelemetryService.instance.navigatorObservers,
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
