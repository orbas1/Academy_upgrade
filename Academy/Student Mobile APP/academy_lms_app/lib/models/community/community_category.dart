class CommunityCategoryModel {
  final int? id;
  final String slug;
  final String name;
  final String? tagline;
  final String? description;
  final String? iconPath;
  final String? colorHex;
  final int sortOrder;

  const CommunityCategoryModel({
    this.id,
    required this.slug,
    required this.name,
    this.tagline,
    this.description,
    this.iconPath,
    this.colorHex,
    this.sortOrder = 0,
  });

  factory CommunityCategoryModel.fromJson(Map<String, dynamic> json) {
    return CommunityCategoryModel(
      id: json['id'] as int?,
      slug: json['slug'] as String,
      name: json['name'] as String,
      tagline: json['tagline'] as String?,
      description: json['description'] as String?,
      iconPath: json['icon_path'] as String?,
      colorHex: json['color_hex'] as String?,
      sortOrder: json['sort_order'] as int? ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'slug': slug,
      'name': name,
      'tagline': tagline,
      'description': description,
      'icon_path': iconPath,
      'color_hex': colorHex,
      'sort_order': sortOrder,
    };
  }
}
