import 'package:flutter_test/flutter_test.dart';

import 'package:academy_lms_app/features/communities/state/community_presence_notifier.dart';
import 'package:academy_lms_app/services/realtime/realtime_presence_service.dart';

void main() {
  group('CommunityPresenceNotifier', () {
    test('tracks online members and typing events', () async {
      final notifier = CommunityPresenceNotifier();

      notifier.debugApplyEvent(
        const PresenceMembersSynced(
          communityId: 7,
          members: <PresenceMember>[
            PresenceMember(memberId: 1, displayName: 'Ada'),
            PresenceMember(memberId: 2, displayName: 'Grace'),
          ],
        ),
      );

      expect(notifier.onlineMembersFor(7).length, 2);
      expect(
        notifier.onlineMembersFor(7).map((member) => member.displayName).toList(),
        containsAll(<String>['Ada', 'Grace']),
      );

      notifier.debugApplyEvent(
        PresenceTypingEvent(
          communityId: 7,
          member: const PresenceMember(memberId: 1, displayName: 'Ada'),
          isTyping: true,
          timeout: const Duration(milliseconds: 200),
        ),
      );

      expect(notifier.typingMembersFor(7).map((member) => member.displayName), contains('Ada'));

      await Future<void>.delayed(const Duration(milliseconds: 220));

      expect(notifier.typingMembersFor(7), isEmpty);

      notifier.debugApplyEvent(
        const PresenceMemberStateChanged(
          communityId: 7,
          member: PresenceMember(memberId: 2, displayName: 'Grace'),
          joined: false,
        ),
      );

      final onlineAfterLeave = notifier.onlineMembersFor(7);
      expect(onlineAfterLeave.length, 1);
      expect(onlineAfterLeave.first.displayName, 'Ada');
    });
  });
}
