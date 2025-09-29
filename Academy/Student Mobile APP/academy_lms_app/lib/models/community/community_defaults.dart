const List<Map<String, dynamic>> kDefaultCommunityCategories = [
  {
    'slug': 'general',
    'name': 'General',
    'tagline': 'Campus-wide announcements and wins',
    'description':
        'Cross-program updates, community highlights, and platform release notes.',
    'icon_path': 'communities/icons/general.svg',
    'color_hex': '#2563eb',
    'sort_order': 1,
  },
  {
    'slug': 'study-groups',
    'name': 'Study Groups',
    'tagline': 'Peer cohorts working through the same track',
    'description':
        'Organize by subject and track to collaborate on assignments and challenges.',
    'icon_path': 'communities/icons/study-groups.svg',
    'color_hex': '#7c3aed',
    'sort_order': 2,
  },
  {
    'slug': 'instructors',
    'name': 'Instructors',
    'tagline': 'Faculty coordination hub',
    'description': 'Content planning, rubric reviews, and classroom best practices.',
    'icon_path': 'communities/icons/instructors.svg',
    'color_hex': '#059669',
    'sort_order': 3,
  },
  {
    'slug': 'alumni',
    'name': 'Alumni',
    'tagline': 'Where alumni mentor and recruit',
    'description': 'Industry mentorship, job leads, and success spotlights.',
    'icon_path': 'communities/icons/alumni.svg',
    'color_hex': '#ea580c',
    'sort_order': 4,
  },
  {
    'slug': 'local-chapters',
    'name': 'Local Chapters',
    'tagline': 'Meet-ups in your city',
    'description': 'Geo-based meetups and localized programming for learners.',
    'icon_path': 'communities/icons/local-chapters.svg',
    'color_hex': '#0ea5e9',
    'sort_order': 5,
  },
];

const List<Map<String, dynamic>> kDefaultCommunityLevels = [
  {
    'level': 1,
    'name': 'Newbie',
    'description': 'Welcome aboard! Start engaging to climb the ranks.',
    'points_required': 0,
  },
  {
    'level': 2,
    'name': 'Contributor',
    'description': 'You are sharing insights and feedback with peers.',
    'points_required': 100,
  },
  {
    'level': 3,
    'name': 'Leader',
    'description': 'Your participation is inspiring the community.',
    'points_required': 500,
  },
  {
    'level': 4,
    'name': 'Champion',
    'description': 'You unlock premium perks and exclusive programming.',
    'points_required': 1500,
  },
];

const List<Map<String, dynamic>> kDefaultCommunityPointsRules = [
  {'action': 'post', 'points': 10, 'cooldown_seconds': 0},
  {'action': 'comment', 'points': 4, 'cooldown_seconds': 0},
  {'action': 'like_received', 'points': 2, 'cooldown_seconds': 0},
  {'action': 'login_streak', 'points': 1, 'cooldown_seconds': 86400},
  {'action': 'course_complete', 'points': 50, 'cooldown_seconds': 0},
  {'action': 'assignment_submit', 'points': 15, 'cooldown_seconds': 0},
];
