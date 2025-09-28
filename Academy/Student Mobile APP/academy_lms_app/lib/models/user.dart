// ignore: depend_on_referenced_packages
import 'package:json_annotation/json_annotation.dart';
part 'user.g.dart';

@JsonSerializable()
class User {
  String? userId;
  String? name;
  String? email;
  String? role;
  String? facebook;
  String? twitter;
  String? linkedIn;
  String? biography;
  String? about;
  String? address;
  String? photo;

  User({
    this.userId,
    this.name,
    this.email,
    this.role,
    this.facebook,
    this.twitter,
    this.linkedIn,
    this.biography,
    this.about,
    this.address,
    this.photo,
  });

  factory User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);

  Map<String, dynamic> toJson() => _$UserToJson(this);
}
