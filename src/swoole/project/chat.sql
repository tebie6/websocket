SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for fd
-- ----------------------------
DROP TABLE IF EXISTS `tb_fd`;
CREATE TABLE `tb_fd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `fd` int(11) NOT NULL DEFAULT '0' COMMENT '绑定id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='用户绑定表';


-- ----------------------------
-- Table structure for msg
-- ----------------------------
DROP TABLE IF EXISTS `tb_msg`;
CREATE TABLE `tb_msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL DEFAULT '' COMMENT '内容',
  `tid` int(11) NOT NULL DEFAULT '0' COMMENT '接收用户id',
  `fid` int(11) NOT NULL DEFAULT '0' COMMENT '发送用户id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='消息表';


