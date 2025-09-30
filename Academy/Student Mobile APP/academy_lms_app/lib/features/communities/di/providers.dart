import 'package:provider/provider.dart';
import 'package:provider/single_child_widget.dart';

import '../../../providers/auth.dart';
import '../data/community_cache.dart';
import '../data/queue_health_repository.dart';
import '../state/community_notifier.dart';
import '../state/community_onboarding_notifier.dart';

List<SingleChildWidget> communityProviders({CommunityCache? cache}) {
  final sharedCache = cache ?? CommunityCache();

  return <SingleChildWidget>[
    Provider<CommunityCache>.value(value: sharedCache),
    ChangeNotifierProvider<CommunityOnboardingNotifier>(
      create: (_) => CommunityOnboardingNotifier(),
    ),
    ProxyProvider<Auth, QueueHealthRepository>(
      update: (_, auth, repository) {
        final repo = repository ?? QueueHealthRepository();
        repo.updateAuthToken(auth.token);
        return repo;
      },
    ),
    ChangeNotifierProxyProvider2<Auth, QueueHealthRepository, CommunityNotifier>(
      create: (_) => CommunityNotifier(
        queueHealthRepository: QueueHealthRepository(),
        cache: sharedCache,
      ),
      update: (_, auth, queueRepo, notifier) {
        final controller = notifier ??
            CommunityNotifier(
              queueHealthRepository: queueRepo,
              cache: sharedCache,
            );

        controller.updateAuthToken(auth.token);
        controller.updateQueueHealthRepository(queueRepo);
        return controller;
      },
    ),
  ];
}
